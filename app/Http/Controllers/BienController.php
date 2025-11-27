<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * @OA\Tag(
 * name="Bienes",
 * description="Endpoints para la gestión del inventario (Bienes)"
 * )
 */
class BienController extends Controller
{
    /**
     * Listar Bienes
     *
     * Muestra una lista paginada de bienes. Permite búsqueda global y filtrado por oficina.
     *
     * @OA\Get(
     * path="/bienes",
     * tags={"Bienes"},
     * summary="Listar y filtrar bienes",
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Término de búsqueda (código, serie, descripción, clave)",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="id_oficina",
     * in="query",
     * description="Filtrar bienes por ID de oficina",
     * required=false,
     * @OA\Schema(type="integer")
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
     * description="Operación exitosa (Paginada)",
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
        // Valida que el frontend esté pidiendo un filtro
        $request->validate([
            'search' => 'nullable|string|min:3',
            'id_oficina' => 'nullable|integer|exists:oficinas,id'
        ]);

        $query = Bien::with([
            'oficina:id,nombre,id_departamento,id_edificio',
            'oficina.departamento:id,dep_nombre,id_area',
            'oficina.departamento.area:id,area_nombre',
            'ubicacionActual:id,nombre',
            'resguardos' => function ($q) {
                $q->latest('resguardo_fecha_asignacion')->limit(1)->with('resguardante:id,res_nombre,res_apellidos');
            }
        ]);

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('bien_codigo', 'like', "%{$term}%")
                  ->orWhere('bien_serie', 'like', "%{$term}%")
                  ->orWhere('bien_descripcion', 'like', "%{$term}%")
                  ->orWhere('bien_clave', 'like', "%{$term}%");
            });
        } 
        else if ($request->filled('id_oficina')) {
            $query->where('id_oficina', $request->input('id_oficina'));
        } 
        else {
             return response()->json(['error' => 'Se requiere un filtro de búsqueda o de oficina.'], 400);
        }

        $bienes = $query->select(
                'id', 'bien_codigo', 'bien_descripcion', 'bien_caracteristicas','bien_serie', 
                'bien_marca', 'bien_modelo', 'bien_estado', 'id_oficina', 'bien_provedor', 'bien_tipo_adquisicion', 'bien_numero_factura', 'bien_valor_monetario', 'bien_ubicacion_actual'
            )
            ->orderBy('id', 'desc')
            ->paginate(25);

        return $bienes;
    }

    /**
     * Crear Bienes (Lotes)s
     *
     * Almacena uno o más bienes generando sus códigos automáticamente en secuencia.
     *
     * @OA\Post(
     * path="/bienes",
     * tags={"Bienes"},
     * summary="Crear bienes (soporta lotes)",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"bien_descripcion", "bien_clave", "bien_y", "cantidad", "bien_valor_monetario"},
     * @OA\Property(property="id_oficina", type="integer", example=1),
     * @OA\Property(property="bien_clave", type="string", description="Clave CAMB", example="I060200310"),
     * @OA\Property(property="bien_y", type="string", description="Año de alta", example="2025"),
     * @OA\Property(property="cantidad", type="integer", description="Cantidad de bienes a generar", example=1),
     * @OA\Property(property="bien_descripcion", type="string", example="Silla ejecutiva"),
     * @OA\Property(property="bien_modelo", type="string", example="X-200"),
     * @OA\Property(property="bien_serie", type="string", example="SN123456"),
     * @OA\Property(property="bien_valor_monetario", type="number", format="float", example=1500.50),
     * @OA\Property(property="bien_marca", type="string", example="Herman Miller"),
     * @OA\Property(property="bien_estado", type="string", example="Bueno"),
     * @OA\Property(property="bien_provedor", type="string"),
     * @OA\Property(property="bien_numero_factura", type="string"),
     * @OA\Property(property="bien_tipo_adquisicion", type="string"),
     * @OA\Property(property="bien_fecha_alta", type="string", format="date")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Bienes creados exitosamente",
     * @OA\JsonContent(type="array", @OA\Items(type="object"))
     * ),
     * @OA\Response(response=500, description="Error en la transacción")
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_oficina' => 'nullable|integer|exists:oficinas,id',
            'bien_modelo' => 'nullable|string|max:255',
            'bien_serie' => 'nullable|string|max:255',
            'bien_descripcion' => 'required|string',
            'bien_caracteristicas' => 'nullable|string',
            'bien_tipo_adquisicion' => 'nullable|string|max:255',
            'bien_fecha_alta' => 'nullable|date',
            'bien_valor_monetario' => 'required|numeric|min:0',
            'bien_provedor' => 'nullable|string|max:255',
            'bien_numero_factura' => 'nullable|string|max:255',
            'bien_estado' => 'nullable|string|max:255',
            'bien_marca' => 'nullable|string|max:255',
            'bien_clave' => 'required|string|max:255',
            'bien_y' => 'required|string|max:4',
            'cantidad' => 'required|integer|min:1',
        ]);

        $bienesCreados = [];
        $claveCamb = $validatedData['bien_clave'];
        $anio = $validatedData['bien_y'];
        $cantidad = (int)$validatedData['cantidad'];

        $baseData = $validatedData;
        unset($baseData['cantidad']);

        $baseData['bien_ubicacion_actual'] = $validatedData['id_oficina'] ?? null;

        try {
            DB::beginTransaction();

            $ultimoBien = Bien::where('bien_clave', $claveCamb)
                                ->orderByRaw('CAST(bien_secuencia AS INTEGER) DESC')
                                ->lockForUpdate()
                                ->first();

            $siguienteSecuenciaNum = $ultimoBien ? (int)$ultimoBien->bien_secuencia + 1 : 1;
            
            $componente_anio = substr($anio, -2);
            $componente_instituto = '23';

            for ($i = 0; $i < $cantidad; $i++) {
                
                $secuenciaActualNum = $siguienteSecuenciaNum + $i;
                $componente_secuencia_str = str_pad($secuenciaActualNum, 5, '0', STR_PAD_LEFT);

                $nuevoCodigo = "{$claveCamb}-{$componente_anio}-{$componente_instituto}-{$componente_secuencia_str}";

                $dataParaEsteBien = $baseData;
                $dataParaEsteBien['bien_codigo'] = $nuevoCodigo;
                $dataParaEsteBien['bien_secuencia'] = (string)$secuenciaActualNum;
                $dataParaEsteBien['bien_serie'] = $validatedData['bien_serie'] ?? 'SIN SERIE';

                $bien = Bien::create($dataParaEsteBien);
                $bienesCreados[] = $bien;
            }

            DB::commit();
            if (count($bienesCreados) > 0) {
                $bienesCreados[0]->load('oficina.departamento.area');
            }

            return response()->json([
                'message' => 'Bienes registrados correctamente',
                'cantidad' => $cantidad,
                'data'    => $bienesCreados
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear el lote de bienes. La operación fue revertida.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver Detalles del Bien
     *
     * Muestra la ficha técnica completa de un bien, incluyendo historial de resguardos, movimientos y traspasos.
     *
     * @OA\Get(
     * path="/bienes/{id}",
     * tags={"Bienes"},
     * summary="Obtener detalles de un bien",
     * @OA\Parameter(name="id", in="path", required=true, description="ID del bien", @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Datos del bien",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(response=404, description="Bien no encontrado")
     * )
     */
    public function show(Bien $biene) // Laravel inyecta el modelo aunque el parametro se llame diferente en la ruta si se configura o si coincide
    {
        // Nota: Asegúrate de que en tu ruta el parámetro sea {biene} o {bien} para que coincida con la variable
        // Si usas apiResource('bienes') el parámetro es {biene} (singular de bienes)
        $biene->load(
            'oficina.departamento.area', 
            'oficina.edificio', 
            'resguardos.resguardante', 
            'movimientosBien', 
            'traspasos.usuarioOrigen'
        );
        return $biene;
    }
/**
     * @OA\Put(
     * path="/api/bienes/{id}",
     * summary="Actualizar, Mover, Dar de Baja o Reactivar un Bien",
     * description="Este endpoint centraliza todas las modificaciones del Bien. El comportamiento depende del campo 'accion'.",
     * tags={"Bienes"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID del Bien",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="El cuerpo de la petición cambia según la acción deseada.",
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(
     * type="object",
     * @OA\Property(property="accion", type="string", enum={"editar_info", "baja", "mover", "reactivar"}, description="Controlador de flujo. Si se omite, se asume 'editar_info'."),
     * @OA\Property(property="bien_marca", type="string", description="Solo para editar_info"),
     * @OA\Property(property="bien_modelo", type="string", description="Solo para editar_info"),
     * @OA\Property(property="motivo_baja", type="string", description="Requerido para accion='baja'"),
     * @OA\Property(property="fecha_baja", type="string", format="date", description="Requerido para accion='baja'"),
     * @OA\Property(property="nuevo_id_oficina", type="integer", description="Requerido para accion='mover'"),
     * @OA\Property(property="nuevo_id_responsable", type="integer", description="Opcional para accion='mover'"),
     * @OA\Property(property="observaciones", type="string", description="Opcional para accion='mover'"),
     * @OA\Property(property="id_oficina", type="integer", description="Requerido para accion='reactivar'"),
     * @OA\Property(property="id_resguardante", type="integer", description="Opcional para accion='reactivar'")
     * ),
     * @OA\Examples(
     * example="1_editar",
     * summary="Caso 1: Editar Información (Default)",
     * value={
     * "accion": "editar_info",
     * "bien_marca": "Dell",
     * "bien_modelo": "Latitude 5420"
     * }
     * ),
     * @OA\Examples(
     * example="2_baja",
     * summary="Caso 2: Dar de Baja",
     * value={
     * "accion": "baja",
     * "fecha_baja": "2024-11-22"
     * }
     * ),
     * @OA\Examples(
     * example="3_mover",
     * summary="Caso 3: Mover (Traspaso)",
     * value={
     * "accion": "mover",
     * "nuevo_id_oficina": 15,
     * "nuevo_id_responsable": 4,
     * }
     * ),
     * @OA\Examples(
     * example="4_reactivar",
     * summary="Caso 4: Reactivar",
     * value={
     * "accion": "reactivar",
     * "id_oficina": 10,
     * "id_resguardante": 45
     * }
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="El bien ha sido dado de baja correctamente"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación (faltan campos requeridos según la acción)"
     * )
     * )
     */
    public function update(Request $request, Bien $biene)
    {
        // 1. Detectamos la intención del usuario
        // Si no envían 'accion', asumimos que es una edición normal.
        $accion = $request->input('accion', 'editar_info');

        // 2. Usamos match (PHP 8) para dirigir el tráfico
        return match ($accion) {
            'baja'   => $this->procesarBaja($request, $biene),
            'mover'  => $this->procesarMovimiento($request, $biene),
            'reactivar' => $this->procesarReactivacion($request, $biene),
            default  => $this->procesarEdicionNormal($request, $biene),
        };
    }



    
    /**
     * Lógica para dar de baja
     */
    protected function procesarBaja(Request $request, Bien $biene)
    {
        $biene->update([
            'bien_estado' => 'Baja',
        ]);

        return response()->json([
        'message' => 'El bien ha sido dado de baja correctamente', 
        'data' => $biene,
    ]);
    }

    /**
     * Lógica para mover/traspasar 
     */
    protected function procesarMovimiento(Request $request, Bien $biene)
    {
        // --- REGLA 1: VALIDAR RESGUARDOS ACTIVOS ---
        // Verificamos si el bien tiene algún registro en la tabla de resguardos.
        // Asumimos que si existe la relación, es que está asignado.
        if ($biene->resguardos()->exists()) {
            return response()->json([
                'message' => 'No se puede mover el bien porque tiene un resguardo asignado. Debe liberar el resguardo primero.'
            ], 409); // 409 Conflict
        }

        // 2. Validación de entrada
        $datos = $request->validate([
            'nuevo_id_oficina' => 'required|exists:oficinas,id',
        ]);

        // --- REGLA 2: ACTUALIZAR ORIGEN Y UBICACIÓN ACTUAL ---
        $biene->update([
            'id_oficina' => $datos['nuevo_id_oficina'],            // Nuevo dueño administrativo
            'bien_ubicacion_actual' => $datos['nuevo_id_oficina'], // Nueva ubicación física
            // Opcional: Si estaba "En tránsito", al llegar a su nueva casa oficial, pasa a Activo
            'bien_estado' => 'ACTIVO' 
        ]);

        return response()->json([
            'message' => 'Bien reubicado correctamente y ubicación física actualizada.', 
            'data' => $biene
        ]);
    }

    /**
     * Edición estándar (Nombre, marca, serie, etc.)
     */
    protected function procesarEdicionNormal(Request $request, Bien $biene)
    {
        // $this->authorize('update', $biene);

        // Utilizamos 'sometimes' para permitir actualizaciones parciales
        $datos = $request->validate([
            'bien_descripcion'      => 'sometimes|required|string',
            'bien_caracteristicas'  => 'sometimes|nullable|string',
            'bien_marca'            => 'sometimes|nullable|string|max:255',
            'bien_modelo'           => 'sometimes|nullable|string|max:255',
            'bien_serie'            => 'sometimes|nullable|string|max:255',
            'bien_estado'           => 'sometimes|required|string|max:50',
            'id_oficina'            => 'sometimes|required|integer|exists:oficinas,id',
            'id_resguardante'       => 'sometimes|nullable|integer|exists:resguardantes,id',
            'bien_provedor'         => 'sometimes|nullable|string|max:255', 
            'bien_fecha_alta'       => 'sometimes|nullable|date',

        ]);

        $biene->update($datos);

        // Refrescamos para devolver el objeto completo y actualizado al frontend
        $biene->refresh();

        return response()->json([
            'message' => 'Información del bien actualizada correctamente', 
            'data' => $biene
        ]);
    }
    /**
     * Lógica para reactivar un bien que estaba de baja.
     * Cambia estado a 'Activo' y lo asigna a una nueva oficina.
     */
    protected function procesarReactivacion(Request $request, Bien $biene)
    {
        $datos = $request->validate([
            'id_oficina' => 'required|exists:oficinas,id',
        ]);

        // 2. Ejecución
        $biene->update([
            'bien_estado' => 'Activo',
            'id_oficina'  => $datos['id_oficina'],
        ]);
        $biene->refresh();

        return response()->json([
            'message' => 'El bien ha sido reactivado y reasignado correctamente',
            'data'    => $biene
        ]);
    }
    /**
     * Comparar Inventario (Auditoría)
     *
     * Compara una lista de códigos escaneados con los bienes registrados en una oficina.
     * Devuelve bienes encontrados, faltantes y sobrantes.
     *
     * @OA\Post(
     * path="/inventario/comparar",
     * tags={"Bienes"},
     * summary="Comparar inventario físico vs sistema",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_oficina", "claves_escaneadas"},
     * @OA\Property(property="id_oficina", type="integer", example=1),
     * @OA\Property(
     * property="claves_escaneadas", 
     * type="array", 
     * @OA\Items(type="string"), 
     * example={"I060200310-25-23-00001", "I060200310-25-23-00002"}
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Resultado de la comparación",
     * @OA\JsonContent(
     * @OA\Property(
     * property="resumen",
     * type="object",
     * @OA\Property(property="total_esperados", type="integer"),
     * @OA\Property(property="total_escaneados", type="integer"),
     * @OA\Property(property="conteo_encontrados", type="integer"),
     * @OA\Property(property="conteo_faltantes", type="integer"),
     * @OA\Property(property="conteo_sobrantes", type="integer")
     * ),
     * @OA\Property(property="encontrados", type="array", @OA\Items(type="object")),
     * @OA\Property(property="faltantes", type="array", @OA\Items(type="object")),
     * @OA\Property(property="sobrantes", type="array", @OA\Items(type="object"))
     * )
     * )
     * )
     */
        /**
     * Eliminar Bien
     *
     * @OA\Delete(
     * path="/bienes/{id}",
     * tags={"Bienes"},
     * summary="Eliminar un bien permanentemente",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=204, description="Bien eliminado"),
     * @OA\Response(response=409, description="Conflicto: El bien tiene registros asociados"),
     * @OA\Response(response=500, description="Error del servidor")
     * )
     */
    public function destroy(Bien $biene)
    {
        try {
            $deleted = $biene->delete();
            if ($deleted) {
                return response()->json(null, 204);
            }

            return response()->json(['error' => 'No se pudo eliminar el registro.'], 500);
        } catch (QueryException $e) {
            return response()->json(['error' => 'No se puede eliminar, tiene registros asociados.'], 409);
        } catch (Throwable $e) {
            return response()->json(['error' => 'Error al eliminar el bien.', 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Post(
     * path="/api/bienes/comparar-inventario",
     * summary="Comparar Inventario Físico vs Teórico",
     * description="Recibe el ID de una oficina y una lista de códigos escaneados (físicos). Retorna un reporte con faltantes, sobrantes y encontrados.",
     * tags={"Inventario"},
     * @OA\RequestBody(
     * required=true,
     * description="Datos del levantamiento de inventario",
     * @OA\JsonContent(
     * required={"id_oficina", "claves_escaneadas"},
     * @OA\Property(property="id_oficina", type="integer", example=15, description="ID de la oficina donde se escanea"),
     * @OA\Property(
     * property="claves_escaneadas",
     * type="array",
     * description="Array con los códigos de barras leídos por la pistola/cámara",
     * @OA\Items(type="string", example="I555-25-23-ABC")
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Reporte generado correctamente",
     * @OA\JsonContent(
     * @OA\Property(
     * property="resumen",
     * type="object",
     * @OA\Property(property="total_esperados", type="integer", example=100, description="Total en base de datos para esa oficina"),
     * @OA\Property(property="total_escaneados", type="integer", example=95, description="Total de códigos enviados"),
     * @OA\Property(property="conteo_encontrados", type="integer", example=90),
     * @OA\Property(property="conteo_faltantes", type="integer", example=10, description="Están en BD pero no se escanearon"),
     * @OA\Property(property="conteo_sobrantes", type="integer", example=5, description="Se escanearon pero no pertenecen a esta oficina")
     * ),
     * @OA\Property(property="encontrados", type="array", @OA\Items(type="object"), description="Lista completa de objetos encontrados"),
     * @OA\Property(property="faltantes", type="array", @OA\Items(type="object"), description="Lista completa de objetos faltantes"),
     * @OA\Property(property="sobrantes", type="array", @OA\Items(type="object"), description="Lista de objetos sobrantes con su oficina real (si existe)")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación en los datos enviados"
     * )
     * )
     */
    public function compararInventario(Request $request)
    {
        $idOficina = $request->input('id_oficina');
        $escaneados = collect($request->input('claves_escaneadas'));

        // 1. Relaciones
        $relacionesBase = [
            'oficina.departamento:id,dep_nombre',
            'ubicacionActual:id,nombre' 
        ];

        // 2. Teóricos
        $bienesTeoricos = Bien::with($relacionesBase)
                            ->where('id_oficina', $idOficina)
                            ->get();
                            
        $clavesTeoricas = $bienesTeoricos->pluck('bien_codigo');

        // 3. ENCONTRADOS Y FALTANTES (Aquí aplicamos el makeHidden extra)
        
        $encontrados = $bienesTeoricos->whereIn('bien_codigo', $escaneados)
                                      ->values()
                                      // Ocultamos fechas Y la llave foránea redundante
                                      ->makeHidden(['created_at', 'updated_at', 'bien_ubicacion_actual']);

        $faltantes = $bienesTeoricos->whereNotIn('bien_codigo', $escaneados)
                                    ->values()
                                    // Igual aquí
                                    ->makeHidden(['created_at', 'updated_at', 'bien_ubicacion_actual']);

        // 4. SOBRANTES
        $clavesSobrantes = $escaneados->diff($clavesTeoricas);
        
        $sobrantesRaw = Bien::whereIn('bien_codigo', $clavesSobrantes)
                            ->with([
                                'oficina:id,nombre,id_departamento', 
                                'ubicacionActual:id,nombre', 
                                'resguardos.resguardante:id,res_nombre,res_apellidos,res_departamento', 
                                'resguardos.resguardante.departamento:id,dep_nombre' 
                            ])
                            ->get();

        // 5. Mapeo (Esto ya estaba bien, no devuelve bien_ubicacion_actual)
        $sobrantesLimpios = $sobrantesRaw->map(function ($bien) {
            $resguardo = $bien->resguardos->first(); 
            $resguardante = $resguardo ? $resguardo->resguardante : null;
            
            $nombreCompleto = $resguardante 
                ? trim(($resguardante->res_nombre ?? '') . ' ' . ($resguardante->res_apellidos ?? ''))
                : 'Sin Resguardante';

            return [
                'id' => $bien->id,
                'codigo' => $bien->bien_codigo,
                'descripcion' => $bien->bien_descripcion,
                'resguardante' => $nombreCompleto,
                'departamento_resguardante' => $resguardante?->departamento?->dep_nombre ?? 'N/A',
                'oficina_pertenencia' => $bien->oficina?->nombre ?? 'Sin Oficina',
                'ubicacion_actual' => $bien->ubicacionActual?->nombre ?? 'No definida'
            ];
        });

        return response()->json([
            'resumen' => [
                'total_esperados'    => $bienesTeoricos->count(),
                'total_escaneados'   => $escaneados->count(),
                'conteo_encontrados' => $encontrados->count(),
                'conteo_faltantes'   => $faltantes->count(),
                'conteo_sobrantes'   => $sobrantesLimpios->count(),
            ],
            'encontrados' => $encontrados, 
            'faltantes'   => $faltantes, 
            'sobrantes'   => $sobrantesLimpios, 
        ]);
    }
    /**
     * @OA\Get(
     * path="/api/bienes/bajas",
     * summary="Listar bienes dados de baja",
     * description="Obtiene el listado histórico de todos los bienes con estado 'Baja', incluyendo su oficina y resguardos asociados.",
     * tags={"Bienes"},
     * @OA\Response(
     * response=200,
     * description="Listado obtenido correctamente",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="cantidad", type="integer", example=15),
     * @OA\Property(
     * property="data",
     * type="array",
     * @OA\Items(
     * type="object",
     * description="Objeto Bien completo",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="bien_codigo", type="string", example="I0-23-XYZ"),
     * @OA\Property(property="bien_estado", type="string", example="Baja"),
     * @OA\Property(property="oficina", type="object", description="Relación con Oficina"),
     * @OA\Property(property="resguardos", type="array", @OA\Items(type="object"), description="Historial de resguardos")
     * )
     * )
     * )
     * )
     * )
     */
    public function bajas()
    {
        // Buscamos todos los bienes donde 'bien_estado' sea 'Baja'
        // Usamos get() para traer la colección completa
        $bienesBaja = Bien::where('bien_estado', 'Baja')
                            ->with(['oficina', 'resguardos']) // Opcional: Carga relaciones para no hacer consultas extra
                            ->get();

        return response()->json([
            'success' => true,
            'cantidad' => $bienesBaja->count(),
            'data' => $bienesBaja
        ], 200);
    }

    /**
     * Busca un bien específico por su código único o número de serie.
     */
    public function buscarPorCodigo($codigo)
    {
        // Limpiamos el input por si viene con espacios accidentales
        $codigo = trim($codigo);

        // Buscamos coincidencia exacta en código o serie
        $bien = Bien::where('bien_codigo', $codigo)
                    ->orWhere('bien_serie', $codigo)
                    ->with(['oficina']) // Cargamos la oficina para contexto visual
                    ->first();

        if (!$bien) {
            return response()->json([
                'message' => 'No se encontró ningún bien con ese código o serie.'
            ], 404);
        }

        // Validación opcional: Verificar si está dado de baja
        if ($bien->bien_estado === 'Baja') {
             return response()->json([
                'message' => 'El bien existe pero está dado de BAJA.',
                'data' => $bien
            ], 409); // 409 Conflict (o 404 si prefieres ocultarlo)
        }

        return response()->json([
            'message' => 'Bien encontrado',
            'data' => $bien
        ], 200);
    }

    public function procesarLevantamiento(Request $request)
    {
        // Validamos
        $request->validate([
            'id_oficina_levantamiento' => 'required|exists:oficinas,id',
            'encontrados' => 'array',
            'faltantes'   => 'array',
            'sobrantes'   => 'array',
        ]);

        $idOficinaFisica = $request->input('id_oficina_levantamiento');

        try {
            DB::transaction(function () use ($request, $idOficinaFisica) {
                
                // 1. BIENES ENCONTRADOS
                if (!empty($request->input('encontrados'))) {
                    Bien::whereIn('bien_codigo', $request->input('encontrados'))
                        ->update([
                            'bien_estado' => 'Activo',
                            'bien_ubicacion_actual' => $idOficinaFisica
                        ]);
                }

                // 2. BIENES FALTANTES
                $faltantes = $request->input('faltantes', []);
                foreach ($faltantes as $item) {
                    // Aquí sí usas ID según tu JSON
                    $bien = Bien::find($item['id']);
                    if (!$bien) continue;

                    if ($item['estado'] === 'En tránsito') {
                        $bien->bien_estado = 'En tránsito';
                        
                        if (isset($item['id_oficina_destino'])) {
                            $bien->bien_ubicacion_actual = $item['id_oficina_destino'];
                        }
                    } else {
                        $bien->bien_estado = 'Extravíado';
                    }
                    $bien->save();
                }

                // 3. BIENES SOBRANTES
                $sobrantes = $request->input('sobrantes', []);
                foreach ($sobrantes as $item) {
                    $bien = Bien::find($item['id']);
                    if (!$bien) continue;

                    if ($item['accion'] === 'ACTUALIZAR_AQUI') {
                        $bien->bien_estado = 'En tránsito';
                        $bien->bien_ubicacion_actual = $idOficinaFisica;
                        
                    } elseif ($item['accion'] === 'REGRESAR_ORIGEN') {
                        $bien->bien_estado = 'Activo';
                        // Regresa a su origen (id_oficina)
                        $bien->bien_ubicacion_actual = $bien->id_oficina; 
                    }
                    $bien->save();
                }
            });

            return response()->json(['message' => 'Levantamiento de inventario procesado correctamente.']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el levantamiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}