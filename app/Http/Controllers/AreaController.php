<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 * name="Areas",
 * description="Endpoints para la gestión de Áreas, Edificios y Responsables"
 * )
 */
class AreaController extends Controller
{
    /**
     * @OA\Get(
     * path="/areas",
     * tags={"Areas"},
     * summary="Listar todas las áreas",
     * description="Retorna un listado de áreas con sus departamentos, edificio y responsable.",
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Area"))
     * )
     * )
     */
    public function index()
    {
        $areas = Area::with([
            'departamentos:id,dep_nombre',
            'edificio:id,nombre',
        ])->get();

        return response()->json($areas);
    }
/**
     * @OA\Post(
     * path="/areas",
     * summary="Crear una nueva área",
     * tags={"Areas"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name"},
     * @OA\Property(property="name", type="string", example="Almacén B", description="Nombre del área"),
     * @OA\Property(property="description", type="string", example="Sector de fríos", description="Descripción opcional")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Área creada exitosamente"
     * )
     * )
     */
    public function store(Request $request)
    {

        $datosValidados = $request->validate([
            'area_nombre' => 'required|string|max:255',
            'area_codigo' => 'required|string|unique:areas,area_codigo',
            'id_edificio' => 'nullable|integer|exists:edificios,id',
        ]);

        $area = Area::create($datosValidados);

        return response()->json($area, 201);
    }
    /**
     * @OA\Get(
     * path="/areas/{id}",
     * summary="Obtener detalles de un área específica",
     * description="Retorna la información del área junto con su edificio, responsable y departamentos asociados.",
     * tags={"Areas"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID del área",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Detalles del área encontrados",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="area_nombre", type="string", example="Dirección de TI"),
     * @OA\Property(property="area_codigo", type="string", example="TI-001"),
     * * @OA\Property(
     * property="edificio",
     * type="object",
     * description="Datos del edificio (cargado con load)",
     * nullable=true,
     * @OA\Property(property="id", type="integer", example=3),
     * @OA\Property(property="nombre", type="string", example="Edificio Central")
     * ),
     * * @OA\Property(
     * property="responsable",
     * type="object",
     * description="Datos del responsable (cargado con load)",
     * nullable=true,
     * @OA\Property(property="id", type="integer", example=10),
     * @OA\Property(property="res_nombre", type="string", example="Carlos"),
     * @OA\Property(property="res_apellidos", type="string", example="López")
     * ),
     * * @OA\Property(
     * property="departamentos",
     * type="array",
     * description="Lista de departamentos asociados",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=45),
     * @OA\Property(property="dep_nombre", type="string", example="Soporte Técnico")
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Área no encontrada"
     * )
     * )
     */
    public function show(string $id)
    {
        $area = Area::findOrFail($id);
        $area->load('departamentos:id,dep_nombre', 
                    'edificio:id,nombre');

        return response()->json($area);
    }

/**
     * @OA\Put(
     * path="/areas/{id}",
     * summary="Actualizar un área existente",
     * tags={"Areas"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID del área a actualizar",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Datos del área a actualizar",
     * @OA\JsonContent(
     * required={"area_nombre", "area_codigo"},
     * @OA\Property(property="area_nombre", type="string", example="Dirección General", description="Nombre del área"),
     * @OA\Property(property="area_codigo", type="string", example="DG-001", description="Código único del área"),
     * @OA\Property(property="id_resguardante_responsable", type="integer", nullable=true, example=5, description="ID del usuario subdirector responsable"),
     * @OA\Property(property="id_edificio", type="integer", nullable=true, example=2, description="ID del edificio asociado")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Área actualizada correctamente",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="area_nombre", type="string", example="Dirección General"),
     * @OA\Property(property="area_codigo", type="string", example="DG-001"),
     * @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación (ej. código duplicado o resguardante inválido)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Área no encontrada"
     * )
     * )
     */
    public function update(Request $request, Area $area)
    {
        // Validación
        $datosValidados = $request->validate([
            'area_nombre' => 'required|string|max:255',
            'area_codigo' => [
                'required',
                'string',
                Rule::unique('areas')->ignore($area->id)
            ],
            'id_edificio' => 'nullable|integer|exists:edificios,id',
        ]);

        // Actualizamos el área
        $area->update($datosValidados);

        // Devolvemos el área actualizada
        return response()->json($area);
    }

    /**
     * @OA\Delete(
     * path="/areas/{id}",
     * summary="Eliminar un área",
     * description="Elimina un área solo si no tiene departamentos ni bienes asociados.",
     * tags={"Areas"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID del área a eliminar",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=204,
     * description="Área eliminada correctamente (Sin contenido)"
     * ),
     * @OA\Response(
     * response=409,
     * description="Conflicto: No se puede eliminar porque tiene dependencias",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No se puede eliminar el área. Aún tiene departamentos asociados.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Área no encontrada"
     * )
     * )
     */
    public function destroy(string $id)
    {
        $area = Area::findOrFail($id); 
    
        // Chequeo 1: Tiene departamentos asociados?
        if ($area->departamentos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el área. Aún tiene departamentos asociados.'
            ], 409); 
        }
        
        // Chequeo 2: Tiene bienes asociados (indirectamente)?
        $departamentoIds = $area->departamentos()->pluck('id');
        $bienesAsociados = Bien::whereIn('bien_id_dep', $departamentoIds)->count();
    
        if ($bienesAsociados > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el área. Sus departamentos aún tienen ' . $bienesAsociados . ' bienes asociados.'
            ], 409);
        }
        $area->delete();
        return response()->noContent();
    }

    /**
     * @OA\Get(
     * path="/areas/{id}/structure",
     * summary="Obtener estructura jerárquica del área",
     * description="Retorna los departamentos del área y sus respectivas oficinas.",
     * tags={"Areas"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID del área",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Estructura completa encontrada",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=10),
     * @OA\Property(property="dep_nombre", type="string", example="Recursos Humanos"),
     * @OA\Property(property="dep_codigo", type="string", example="RH-01"),
     * @OA\Property(property="oficinas", type="array",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=5),
     * @OA\Property(property="nombre", type="string", example="Oficina de Nóminas"),
     * @OA\Property(property="ofi_codigo", type="string", example="NOM-01")
     * )
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Área no encontrada"
     * )
     * )
     */
    public function getStructure(Area $area)
    {
        // Inicia la consulta en los departamentos de ESTA área
        $structure = $area->departamentos()
            ->with([
                // Carga la relación 'oficinas' para CADA departamento
                'oficinas' => function ($query) {
                    // 3. Selecciona solo las columnas que necesitas de las oficinas
                    $query->select('id', 'nombre', 'ofi_codigo', 'id_departamento');
                }
            ])
            // Selecciona solo las columnas que necesitas de los departamentos
            ->select('id', 'dep_nombre', 'dep_codigo', 'id_area')
            ->get();

        return response()->json($structure);
    }

}
