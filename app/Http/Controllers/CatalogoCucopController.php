<?php

namespace App\Http\Controllers;

use App\Models\CatalogoCambCucop;
use App\Models\ConfiguracionInventario; // Asegúrate de importar esto
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @OA\Tag(
 * name="Catálogo CUCOP",
 * description="Endpoints para la gestión y búsqueda de claves CUCOP y CAMB"
 * )
 */
class CatalogoCucopController extends Controller
{
    /**
     * Listar Catálogo (Búsqueda Exacta)
     *
     * Obtiene una lista paginada de registros. Permite buscar por coincidencia exacta en 'camb' o 'clave_cucop'.
     *
     * @OA\Get(
     * path="/catalogo-cucop",
     * tags={"Catálogo CUCOP"},
     * summary="Listar y buscar en el catálogo",
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Término de búsqueda (CAMB o Clave CUCOP exacta)",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Número de página",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista de registros paginada",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="data", type="array", @OA\Items(type="object")),
     * @OA\Property(property="current_page", type="integer"),
     * @OA\Property(property="total", type="integer")
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        // 1. Columnas a seleccionar
        $columns = ['id','clave_cucop', 'partida_especifica', 'descripcion', 'camb'];

        // 2. Iniciar la consulta
        $query = CatalogoCambCucop::select($columns);

        // 3. Aplicar la lógica de búsqueda (si se proporciona)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                
                // 1. Siempre busca en la columna de texto (camb)
                $q->where('camb', '=', $searchTerm);
                //    busca también en la columna de número (clave_cucop)
                if (is_numeric($searchTerm)) {
                    $q->orWhere('clave_cucop', '=', (int)$searchTerm);
                }
            });
        }

        // 4. Obtener los resultados con paginación (más reciente -> más antiguo)
        $catalogo = $query->orderBy('id', 'desc')->paginate(15);

        return $catalogo;
    }

    /**
     * Crear Registro en Catálogo
     *
     * @OA\Post(
     * path="/catalogo-cucop",
     * tags={"Catálogo CUCOP"},
     * summary="Crear un nuevo registro CUCOP/CAMB",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"tipo", "clave_cucop", "descripcion"},
     * @OA\Property(property="tipo", type="string", example="1", description="Requerido por validación"),
     * @OA\Property(property="clave_cucop", type="integer", example=12345678),
     * @OA\Property(property="partida_especifica", type="string", example="21101"),
     * @OA\Property(property="clave_cucop_plus", type="string"),
     * @OA\Property(property="descripcion", type="string", example="Material de oficina"),
     * @OA\Property(property="nivel", type="string", example="5"),
     * @OA\Property(property="camb", type="string", example="C12345"),
     * @OA\Property(property="unidad_medida", type="string", example="Pieza"),
     * @OA\Property(property="tipo_contratacion", type="string", example="Adquisiciones")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Registro creado exitosamente",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(response=422, description="Error de validación"),
     * @OA\Response(response=500, description="Error del servidor")
     * )
     */


    public function store(Request $request)
    {
        // 1. Validaciones
        $request->validate([
            'partida_especifica' => 'required|string|min:3', 
            'descripcion' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $data = $request->all();
            
            // --- 1. LÓGICA CUCOP (Se mantiene igual) ---
            $prefijoPartida = substr($request->partida_especifica, 0, 3); // "511"
            
            $ultimoCucop = CatalogoCambCucop::where('clave_cucop', 'like', $prefijoPartida . '%')
                ->orderByRaw('CAST(clave_cucop AS INTEGER) DESC')
                ->lockForUpdate()
                ->first();

            $secCucop = 1;
            if ($ultimoCucop) {
                $claveCompleta = (string)$ultimoCucop->clave_cucop; 
                $secuenciaActual = intval(substr($claveCompleta, -4)); 
                $secCucop = $secuenciaActual + 1;
            }
            $data['clave_cucop'] = $prefijoPartida . str_pad($secCucop, 4, '0', STR_PAD_LEFT);


            // --- 2. LÓGICA CAMB (ROBUSTA SIN SEPARADORES) ---
            
            // A. Cargar la configuración de etiquetas
            // (Asumimos ID 1 o la lógica de tu tenant)
            $configModel = ConfiguracionInventario::first();
            $config = $configModel ? $configModel->configuracion_json : [];
            $structure = $config['structure'] ?? [];
            $prefixes = $config['prefixes'] ?? [];

            // B. Determinar Variables Base
            $familia = substr($request->partida_especifica, 0, 3); // "511"
            
            // Buscar prefijo configurado (Ej. "MUE" o "I51101")
            // Nota: Laravel Collections facilita la búsqueda en el array de JSON
            $foundPrefix = collect($prefixes)->firstWhere('api_code', $familia);
            $catPrefix = $foundPrefix ? $foundPrefix['prefix'] : 'GEN';

            // C. Calcular la Secuencia de Categoría
            // Buscamos cuántos existen con este prefijo de categoría para asignar el siguiente número
            // IMPORTANTE: Buscamos por "contiene el prefijo al inicio" independientemente de los separadores
            
            // Para ser precisos, filtramos los que tengan la partida específica en la BD o usamos el prefijo.
            // Usaremos el prefijo de categoría como identificador de la serie.
            
            // Obtenemos el último CAMB que empiece con este prefijo de categoría
            // Ej: Buscar 'I51101%'
            $ultimoCamb = CatalogoCambCucop::where('camb', 'like', $catPrefix . '%')
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $secCamb = 1;
            
            // Aquí viene el truco: Como no podemos hacer explode si no hay separador,
            // necesitamos guardar la secuencia en una columna aparte O extraerla con inteligencia.
            // Para este caso, extraeremos intentando limpiar el prefijo.
            
            if ($ultimoCamb) {
                // "I5110100012523"
                $cambAnterior = $ultimoCamb->camb;
                
                // Quitamos el prefijo del inicio
                // "00012523"
                $resto = substr($cambAnterior, strlen($catPrefix));
                
                // Quitamos posibles separadores al inicio del resto
                $separator = $structure['separator'] ?? '';
                if ($separator) {
                    $resto = ltrim($resto, $separator);
                }
                
                // Asumimos que la secuencia está al inicio del resto (0001...)
                // Tomamos los dígitos de la longitud configurada (ej. 4)
                $len = $structure['zerosLength'] ?? 4;
                $posibleSecuencia = substr($resto, 0, $len);
                
                if (is_numeric($posibleSecuencia)) {
                    $secCamb = intval($posibleSecuencia) + 1;
                }
            }
            
            $secuenciaStr = str_pad($secCamb, $structure['zerosLength'] ?? 4, '0', STR_PAD_LEFT);

            // D. Reconstruir el CAMB Final (Server Side)
            // Usamos la misma lógica de "piezas" que en el Frontend, pero segura.
            
            $p_year = '';
            if (!empty($structure['includeYear'])) {
                $yearFormat = $structure['yearFormat'] ?? 'YYYY';
                $p_year = ($yearFormat === 'YY') ? date('y') : date('Y');
            }
            
            $p_inst = '';
            if (!empty($structure['includeInstitution'])) {
                $p_inst = $structure['institutionPrefix'] ?? 'INST';
            }
            
            $sep = $structure['separator'] === 'Ninguno' ? '' : ($structure['separator'] ?? ''); // Si es "Ninguno" en el select del front, pon vacío

            // Armamos el array de partes en orden
            $parts = [];

            // 1. Inicio (Inst + Cat o Cat + Inst)
            if (!empty($structure['includeInstitution'])) {
                $pos = $structure['institutionPosition'] ?? 'start';
                if ($pos === 'start') {
                    $parts[] = $p_inst;
                    $parts[] = $catPrefix;
                } elseif ($pos === 'middle') {
                    $parts[] = $catPrefix;
                    $parts[] = $p_inst;
                } else {
                    $parts[] = $catPrefix;
                }
            } else {
                $parts[] = $catPrefix;
            }

            // 2. Secuencia (Va aquí según tu lógica de negocio)
            $parts[] = $secuenciaStr;

            // 3. Año
            if ($p_year) $parts[] = $p_year;

            // 4. Institución al final
            if (!empty($structure['includeInstitution']) && ($structure['institutionPosition'] ?? '') === 'before_seq') {
                // Nota: En tu lógica anterior 'before_seq' iba al final. Ajusta si necesario.
                $parts[] = $p_inst;
            }

            // 5. UNIR TODO
            // array_filter quita vacíos por si acaso
            $data['camb'] = implode($sep, array_filter($parts, fn($v) => $v !== '')); 

            // ----------------------------------------

            $data['tipo'] = 1;
            $data['nivel'] = 1;

            $catalogo = CatalogoCambCucop::create($data);

            return response()->json([
                'message' => 'Registro creado. CAMB: ' . $data['camb'],
                'data' => $catalogo
            ], 201);
        });
    }


    /**
     * Ver Registro
     *
     * @OA\Get(
     * path="/catalogo-cucop/{id}",
     * tags={"Catálogo CUCOP"},
     * summary="Obtener detalles de un registro",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Datos del registro"),
     * @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(CatalogoCambCucop $catalogo)
    {
        return response()->json($catalogo);
    }

    /**
     * Actualizar Registro
     *
     * @OA\Put(
     * path="/catalogo-cucop/{id}",
     * tags={"Catálogo CUCOP"},
     * summary="Actualizar un registro existente",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="tipo", type="string"),
     * @OA\Property(property="clave_cucop", type="integer"),
     * @OA\Property(property="partida_especifica", type="string"),
     * @OA\Property(property="descripcion", type="string"),
     * @OA\Property(property="camb", type="string")
     * )
     * ),
     * @OA\Response(response=200, description="Registro actualizado"),
     * @OA\Response(response=422, description="Error de validación o clave duplicada")
     * )
     */
    public function update(Request $request, CatalogoCambCucop $catalogo)
    {
        $validatedData = $request->validate([
            'clave_cucop' => [
                'required',
                'integer',
                Rule::unique('catalogo_camb_cucop', 'clave_cucop')->ignore($catalogo->getKey()),
            ],
            'descripcion' => 'required|string',
            'camb' => 'nullable|string|max:255',

        ]);

        // Forzamos los valores constantes
        $validatedData['tipo'] = '1';
        $validatedData['nivel'] = '5';
        $validatedData['unidad_medida'] = 'pieza';
        $validatedData['tipo_contratacion'] = 'adquisiciones';

        try {
            $catalogo->update($validatedData);
            return response()->json($catalogo, 200);
        } catch (Throwable $e) {
            return response()->json(['error' => 'No se pudo actualizar el registro.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar Registro
     *
     * @OA\Delete(
     * path="/catalogo-cucop/{id}",
     * tags={"Catálogo CUCOP"},
     * summary="Eliminar un registro",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=204, description="Eliminado exitosamente"),
     * @OA\Response(response=500, description="Error del servidor")
     * )
     */
    public function destroy(CatalogoCambCucop $catalogo)
    {
        try {
            $catalogo->delete();
            return response()->json(null, 204); // 204 No Content
        } catch (Throwable $e) {
            // Esto capturará excepciones como QueryException si hay FKs asociados
            return response()->json(['error' => 'No se pudo eliminar el registro.', 'message' => $e->getMessage()], 500);
        }
    }
}