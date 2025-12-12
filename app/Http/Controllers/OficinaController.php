<?php

namespace App\Http\Controllers;

use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class OficinaController extends Controller
{
    /**
     * @OA\Get(
     * path="/oficinas",
     * summary="Listar oficinas",
     * description="Obtiene un listado paginado de oficinas, incluyendo la relación con edificio.",
     * tags={"Oficinas"},
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Buscar por nombre o referencia",
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
     * description="Listado de oficinas",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="nombre", type="string", example="Oficina Administrativa"),
     * @OA\Property(property="ofi_codigo", type="string", example="ADM-001"),
     * @OA\Property(property="referencia", type="string", example="Primer piso, puerta izquierda"),
     * @OA\Property(property="edificio", type="object",
     * @OA\Property(property="id", type="integer", example=5),
     * @OA\Property(property="nombre", type="string", example="Edificio Central")
     * )
     * )),
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="total", type="integer", example=50)
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        $query = Oficina::with('edificio'); 

        // Búsqueda por nombre o referencia
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('nombre', 'like', "%{$searchTerm}%")
                  ->orWhere('referencia', 'like', "%{$searchTerm}%");
            });
        }

        $oficinas = $query->latest()->paginate(10);
        
        return $oficinas;
    }

    /**
     * @OA\Post(
     * path="/oficinas",
     * summary="Crear nueva oficina",
     * tags={"Oficinas"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_departamento", "id_edificio", "ofi_codigo", "nombre"},
     * @OA\Property(property="id_departamento", type="integer", example=10, description="ID del departamento al que pertenece"),
     * @OA\Property(property="id_edificio", type="integer", example=3, description="ID del edificio físico"),
     * @OA\Property(property="ofi_codigo", type="string", example="OF-202", description="Código único de la oficina"),
     * @OA\Property(property="nombre", type="string", example="Sala de Juntas B"),
     * @OA\Property(property="referencia", type="string", example="Junto al elevador")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Oficina creada exitosamente",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=15),
     * @OA\Property(property="nombre", type="string", example="Sala de Juntas B"),
     * @OA\Property(property="departamento", type="object", description="Objeto departamento cargado"),
     * @OA\Property(property="edificio", type="object", description="Objeto edificio cargado")
     * )
     * ),
     * @OA\Response(response=422, description="Error de validación (ej. código duplicado)")
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_departamento' => 'required|integer|exists:departamentos,id',
            'id_edificio' => 'required|exists:edificios,id',
            'ofi_codigo' => 'required|string|max:255|unique:oficinas,ofi_codigo',
            'nombre' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
        ]);

        $oficina = Oficina::create($validatedData);

        // Devolvemos la oficina creada, cargando la relación 'edificio'
        return response()->json($oficina->load('departamento','edificio'), 201);
    }

    /**
     * @OA\Get(
     * path="/oficinas/{id}",
     * summary="Obtener detalles de una oficina",
     * tags={"Oficinas"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Información de la oficina",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="nombre", type="string", example="Oficina Principal"),
     * @OA\Property(property="edificio", type="object", description="Datos del edificio")
     * )
     * ),
     * @OA\Response(response=404, description="Oficina no encontrada")
     * )
     */
    public function show(Oficina $oficina)
    {
        return $oficina->load('edificio');
    }

    /**
     * @OA\Put(
     * path="/oficinas/{id}",
     * summary="Actualizar oficina",
     * tags={"Oficinas"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_departamento", "id_edificio", "ofi_codigo", "nombre"},
     * @OA\Property(property="id_departamento", type="integer", example=10),
     * @OA\Property(property="id_edificio", type="integer", example=3),
     * @OA\Property(property="ofi_codigo", type="string", example="OF-202-UPDATED"),
     * @OA\Property(property="nombre", type="string", example="Sala de Juntas Actualizada"),
     * @OA\Property(property="referencia", type="string", example="Nueva referencia")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Oficina actualizada",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=15),
     * @OA\Property(property="nombre", type="string", example="Sala de Juntas Actualizada")
     * )
     * ),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Oficina $oficina)
    {
        $validatedData = $request->validate([
            'id_departamento' => 'required|integer|exists:departamentos,id',
            'id_edificio' => 'required|exists:edificios,id',
            'ofi_codigo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('oficinas')->ignore($oficina->id),
            ],
            'nombre' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
        ]);

        $oficina->update($validatedData);

        return response()->json($oficina->load('departamento','edificio'), 200);
    }

    /**
     * @OA\Delete(
     * path="/oficinas/{id}",
     * summary="Eliminar oficina",
     * description="Elimina la oficina solo si no tiene bienes asociados.",
     * tags={"Oficinas"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=204, description="Eliminada correctamente (No Content)"),
     * @OA\Response(
     * response=409,
     * description="Conflicto: La oficina tiene bienes asignados",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="No se puede eliminar la oficina. Aún tiene bienes asociados."))
     * ),
     * @OA\Response(response=404, description="Oficina no encontrada")
     * )
     */
    public function destroy(Oficina $oficina)
    {
        // 1. Comprobación de si tiene bienes asociados
        if ($oficina->bienes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la oficina. Aún tiene bienes asociados.'
            ], 409); 
        }
        // 2. Si todo está limpio, se elimina
        $oficina->delete();
        
        return response()->json(null, 204); 
    }
    /**
     * @OA\Get(
     * path="/oficinas/{id}/bienes",
     * summary="Obtener bienes de una oficina",
     * description="Lista paginada de bienes asignados a esta oficina con filtros avanzados.",
     * tags={"Oficinas"},
     * @OA\Parameter(name="id", in="path", description="ID de la oficina", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Buscar bienes por código, serie o descripción dentro de la oficina",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="estado",
     * in="query",
     * description="Filtrar por estado del bien (ej. 'Activo', 'Baja')",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="sin_resguardo",
     * in="query",
     * description="Si es 'true', devuelve solo bienes que no tienen dueño asignado",
     * required=false,
     * @OA\Schema(type="boolean")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de bienes",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=101),
     * @OA\Property(property="bien_codigo", type="string", example="C-500"),
     * @OA\Property(property="bien_descripcion", type="string", example="Silla Ergonómica"),
     * @OA\Property(property="bien_estado", type="string", example="Activo"),
     * @OA\Property(property="id_resguardante", type="integer", nullable=true, description="ID del dueño actual (null si está libre)"),
     * @OA\Property(property="ubicacionActual", type="object", @OA\Property(property="nombre", type="string")),
     * @OA\Property(property="resguardos", type="array", description="Historial breve (último resguardo)", @OA\Items(type="object"))
     * )),
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="total", type="integer", example=25)
     * )
     * )
     * )
     */
    public function getBienes(Request $request, Oficina $oficina)
    {
        // 1. Inicia la consulta
        $query = $oficina->bienes()->with([
            'ubicacionActual:id,nombre',
            'resguardos' => function ($q) {
                $q->latest('resguardo_fecha_asignacion')->limit(1)->with('resguardante:id,res_nombre,res_apellidos');
            }
        ]);

        // 2. Filtro por estado (Existente)
        if ($request->filled('estado')) {
            $query->where('bien_estado', $request->input('estado'));
        }

        // 3. Validamos que no tenga resguardo
        // Si el front envía ?sin_resguardo=true, filtramos los que ya tienen dueño
        if ($request->has('sin_resguardo') && $request->input('sin_resguardo') == 'true') {
            $query->whereNull('id_resguardante');            // si id_resguardante es NULL, el bien está libre en la oficina
        }

        // 4. Buscar DENTRO de la oficina (Existente)
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('bien_codigo', 'like', "%{$term}%")
                ->orWhere('bien_serie', 'like', "%{$term}%")
                ->orWhere('bien_descripcion', 'like', "%{$term}%");
            });
        }

        // 5. Selección y Paginación
        $bienes = $query->select(
            'id', 'bien_codigo', 'bien_descripcion', 'bien_serie', 'bien_caracteristicas',
            'bien_marca', 'bien_modelo', 'bien_estado', 'id_oficina', 'bien_provedor',
            'bien_tipo_adquisicion', 'bien_numero_factura', 'bien_valor_monetario',
            'bien_ubicacion_actual', 'bien_foto', 'id_resguardante' 
        )
        ->orderBy('id', 'desc')
        ->paginate(15);

        return $bienes;
    }
}