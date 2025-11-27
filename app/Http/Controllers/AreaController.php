<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 * name="Áreas",
 * description="Endpoints para la gestión de Áreas Generales (ej. Subdirecciones)"
 * )
 */
class AreaController extends Controller
{
    /**
     * Listar Áreas
     *
     * Obtiene todas las áreas registradas, incluyendo información resumida de sus departamentos, edificio y responsable.
     *
     * @OA\Get(
     * path="/areas",
     * tags={"Áreas"},
     * summary="Listar todas las áreas",
     * @OA\Response(
     * response=200,
     * description="Lista de áreas",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(type="object")
     * )
     * )
     * )
     */
    public function index()
    {
        $areas = Area::with([
            'departamentos:id,dep_nombre',
            'edificio:id,nombre',
            'responsable:id,res_nombre,res_apellidos' 
        ])->get();

        return response()->json($areas);
    }

    /**
     * Crear Área
     *
     * Registra una nueva área. Valida que el responsable tenga el rol de 'Subdirector'.
     *
     * @OA\Post(
     * path="/areas",
     * tags={"Áreas"},
     * summary="Crear una nueva área",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"area_nombre", "area_codigo"},
     * @OA\Property(property="area_nombre", type="string", example="Subdirección Académica"),
     * @OA\Property(property="area_codigo", type="string", example="002"),
     * @OA\Property(property="id_resguardante_responsable", type="integer", description="ID del resguardante (Debe ser Subdirector)", nullable=true),
     * @OA\Property(property="id_edificio", type="integer", description="ID del edificio principal", nullable=true)
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Área creada exitosamente",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación (código duplicado o responsable inválido)"
     * )
     * )
     */
    public function store(Request $request)
    {
        // Validación de la Regla 2: Responsable debe ser Subdirector
        $subdirectores = DB::table('resguardantes')
            ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id')
            ->where('roles.rol_nombre', 'Subdirector') // Ajusta este nombre de rol
            ->pluck('resguardantes.id');

        $datosValidados = $request->validate([
            'area_nombre' => 'required|string|max:255',
            'area_codigo' => 'required|string|unique:areas,area_codigo',
            
            // Validaciones de las llaves foráneas (nulables)
            'id_resguardante_responsable' => [
                'nullable',
                'integer',
                Rule::in($subdirectores) // ¡La regla de negocio clave!
            ],
            'id_edificio' => 'nullable|integer|exists:edificios,id',
            //'id_departamento' => 'nullable|integer|exists:departamentos,id',
        ]);

        $area = Area::create($datosValidados);

        return response()->json($area, 201);
    }

    /**
     * Ver Área
     *
     * Muestra los detalles de un área específica.
     *
     * @OA\Get(
     * path="/areas/{id}",
     * tags={"Áreas"},
     * summary="Obtener detalles de un área",
     * @OA\Parameter(name="id", in="path", required=true, description="ID del área", @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Datos del área",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(response=404, description="Área no encontrada")
     * )
     */
    public function show(string $id)
    {
        $area = Area::findOrFail($id);
        $area->load('departamentos:id,dep_nombre', 
                    'edificio:id,nombre', 
                    'responsable:id,res_nombre,res_apellidos');

        return response()->json($area);
    }

    /**
     * Actualizar Área
     *
     * @OA\Put(
     * path="/areas/{id}",
     * tags={"Áreas"},
     * summary="Actualizar información de un área",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"area_nombre", "area_codigo"},
     * @OA\Property(property="area_nombre", type="string"),
     * @OA\Property(property="area_codigo", type="string"),
     * @OA\Property(property="id_resguardante_responsable", type="integer", nullable=true),
     * @OA\Property(property="id_edificio", type="integer", nullable=true)
     * )
     * ),
     * @OA\Response(response=200, description="Área actualizada"),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Area $area)
    {
        $subdirectoresIds = DB::table('resguardantes')
            ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id')
            ->where('roles.rol_nombre', 'Subdirector')
            ->pluck('resguardantes.id');

        // 2. Validación
        $datosValidados = $request->validate([
            'area_nombre' => 'required|string|max:255',
            
            'area_codigo' => [
                'required',
                'string',
                Rule::unique('areas')->ignore($area->id) // Ignora el ID actual
            ],
            
            'id_resguardante_responsable' => [
                'nullable',
                'integer',
                Rule::in($subdirectoresIds) // Valida que el ID esté en la lista
            ],
            'id_edificio' => 'nullable|integer|exists:edificios,id',
        ]);

        // 3. Actualizamos el área
        $area->update($datosValidados);

        // 4. Devolvemos el área actualizada
        return response()->json($area);
    }

    /**
     * Eliminar Área
     *
     * @OA\Delete(
     * path="/areas/{id}",
     * tags={"Áreas"},
     * summary="Eliminar un área",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=204,
     * description="Eliminado exitosamente"
     * ),
     * @OA\Response(
     * response=409,
     * description="Conflicto: No se puede eliminar porque tiene departamentos o bienes asociados"
     * ),
     * @OA\Response(response=500, description="Error del servidor")
     * )
     */
    public function destroy(string $id)
    {
        $area = Area::findOrFail($id); // Busca el área primero
    
        // Chequeo 1: ¿Tiene departamentos asociados?
        if ($area->departamentos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el área. Aún tiene departamentos asociados.'
            ], 409); // 409 Conflict
        }
        
        // Chequeo 2: ¿Tiene bienes asociados (indirectamente)?
        $departamentoIds = $area->departamentos()->pluck('id');
        $bienesAsociados = Bien::whereIn('bien_id_dep', $departamentoIds)->count();
    
        if ($bienesAsociados > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el área. Sus departamentos aún tienen ' . $bienesAsociados . ' bienes asociados.'
            ], 409);
        }
    
        // Si todo está limpio, se elimina
        $area->delete();
        return response()->noContent();
    }

    /**
     * Obtener Estructura Jerárquica
     *
     * Devuelve la lista de departamentos y oficinas que pertenecen a esta área.
     *
     * @OA\Get(
     * path="/areas/{id}/structure",
     * tags={"Áreas"},
     * summary="Obtener estructura (Deptos -> Oficinas)",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Estructura jerárquica",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="dep_nombre", type="string"),
     * @OA\Property(
     * property="oficinas",
     * type="array",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="nombre", type="string"),
     * @OA\Property(property="ofi_codigo", type="string")
     * )
     * )
     * )
     * )
     * )
     * )
     */
    public function getStructure(Area $area)
    {
        // 1. Inicia la consulta en los departamentos de ESTA área
        $structure = $area->departamentos()
            ->with([
                // 2. Carga la relación 'oficinas' para CADA departamento
                'oficinas' => function ($query) {
                    // 3. Selecciona solo las columnas que necesitas de las oficinas
                    $query->select('id', 'nombre', 'ofi_codigo', 'id_departamento');
                }
            ])
            // 4. Selecciona solo las columnas que necesitas de los departamentos
            ->select('id', 'dep_nombre', 'dep_codigo', 'id_area')
            ->get();

        // 5. Devuelve el JSON
        return response()->json($structure);
    }
}