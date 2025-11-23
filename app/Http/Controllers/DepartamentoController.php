<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 * name="Departamentos",
 * description="Endpoints para la gestión de Departamentos dentro de las Áreas"
 * )
 */
class DepartamentoController extends Controller
{
    /**
     * Listar Departamentos
     *
     * Muestra una lista paginada de los departamentos registrados, incluyendo su área relacionada.
     *
     * @OA\Get(
     * path="/departamentos",
     * tags={"Departamentos"},
     * summary="Listar departamentos",
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Número de página",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista de departamentos paginada",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="data", type="array", @OA\Items(type="object")),
     * @OA\Property(property="current_page", type="integer"),
     * @OA\Property(property="total", type="integer")
     * )
     * )
     * )
     */
    public function index()
    {
        // Incluimos la relación 'area' para saber su nombre
        $departamentos = Departamento::with('area')->latest()->paginate(10);
        return $departamentos;
    }

    /**
     * Crear Departamento
     *
     * Almacena un nuevo departamento en la base de datos.
     *
     * @OA\Post(
     * path="/departamentos",
     * tags={"Departamentos"},
     * summary="Crear un nuevo departamento",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"dep_nombre", "dep_codigo", "id_area"},
     * @OA\Property(property="dep_nombre", type="string", example="Recursos Humanos"),
     * @OA\Property(property="dep_codigo", type="string", example="DEP-RH-001"),
     * @OA\Property(property="id_area", type="integer", description="ID del Área a la que pertenece", example=1),
     * @OA\Property(property="dep_resposable", type="string", description="Nombre del responsable (opcional)", example="Lic. Ana Méndez"),
     * @OA\Property(property="dep_correo_institucional", type="string", format="email", example="rh@example.com")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Departamento creado exitosamente",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación (código duplicado o área no existente)"
     * )
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'dep_nombre' => 'required|string|max:255',
            'dep_codigo' => 'required|string|max:255|unique:departamentos,dep_codigo',
            'dep_resposable' => 'nullable|string|max:255',
            'dep_correo_institucional' => 'nullable|email|max:255',
            'id_area' => 'required|exists:areas,id',
        ]);

        $departamento = Departamento::create($validatedData);
        return response()->json($departamento->load('area'), 201);
    }

    /**
     * Ver Departamento
     *
     * Muestra los detalles de un departamento específico.
     *
     * @OA\Get(
     * path="/departamentos/{id}",
     * tags={"Departamentos"},
     * summary="Obtener detalles de un departamento",
     * @OA\Parameter(name="id", in="path", required=true, description="ID del departamento", @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Datos del departamento",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(response=404, description="Departamento no encontrado")
     * )
     */
    public function show(Departamento $departamento)
    {
        // Carga la relación 'area' y devuelve el JSON
        return $departamento->load('area');
    }

    /**
     * Actualizar Departamento
     *
     * @OA\Put(
     * path="/departamentos/{id}",
     * tags={"Departamentos"},
     * summary="Actualizar un departamento existente",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"dep_nombre", "dep_codigo", "id_area"},
     * @OA\Property(property="dep_nombre", type="string"),
     * @OA\Property(property="dep_codigo", type="string"),
     * @OA\Property(property="id_area", type="integer"),
     * @OA\Property(property="dep_resposable", type="string"),
     * @OA\Property(property="dep_correo_institucional", type="string")
     * )
     * ),
     * @OA\Response(response=200, description="Departamento actualizado"),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Departamento $departamento)
    {
        $validatedData = $request->validate([
            'dep_nombre' => 'required|string|max:255',
            'dep_codigo' => 'required|string|max:255|unique:departamentos,dep_codigo,' . $departamento->id,
            'dep_resposable' => 'nullable|string|max:255',
            'dep_correo_institucional' => 'nullable|email|max:255',
            'id_area' => 'required|exists:areas,id',
        ]);

        $departamento->update($validatedData);

        return response()->json($departamento->load('area'), 200);
    }

    /**
     * Eliminar Departamento
     *
     * @OA\Delete(
     * path="/departamentos/{id}",
     * tags={"Departamentos"},
     * summary="Eliminar un departamento",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=204,
     * description="Eliminado exitosamente"
     * ),
     * @OA\Response(
     * response=409,
     * description="Conflicto: No se puede eliminar porque tiene resguardantes u oficinas asociadas"
     * ),
     * @OA\Response(
     * response=500,
     * description="Error del servidor"
     * )
     * )
     */
    public function destroy(Departamento $departamento)
    {
        try {
            $departamento->delete();
            return response()->json(null, 204); // Éxito, sin contenido

        } catch (QueryException $e) {
            // Manejar error de llave foránea (si el depto tiene resguardantes)
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1451 || str_contains($e->getMessage(), 'foreign key constraint fails')) {
                return response()->json([
                    'error' => 'No se puede eliminar el departamento porque tiene resguardantes u otros registros asociados.'
                ], 409); // 409 Conflicto
            }
            
            // Otro error de base de datos
            return response()->json(['error' => 'Error al eliminar el recurso.'], 500);
        }
    }
}