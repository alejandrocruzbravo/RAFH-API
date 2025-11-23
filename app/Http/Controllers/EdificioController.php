<?php

namespace App\Http\Controllers;

use App\Models\Edificio;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 * name="Edificios",
 * description="Endpoints para la gestión de los Edificios de la institución"
 * )
 */
class EdificioController extends Controller
{
    /**
     * Listar Edificios
     *
     * Muestra una lista paginada de los edificios registrados.
     *
     * @OA\Get(
     * path="/edificios",
     * tags={"Edificios"},
     * summary="Listar todos los edificios",
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Número de página",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de edificios",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="nombre", type="string", example="Edificio A")
     * )),
     * @OA\Property(property="current_page", type="integer"),
     * @OA\Property(property="total", type="integer")
     * )
     * )
     * )
     */
    public function index()
    {
        // Para una lista simple, no paginamos.
        // Si esperas tener CIENTOS de edificios, cambia get() por paginate(15).
        $edificios = Edificio::orderBy('nombre')->paginate(10);
        return $edificios;
    }

    /**
     * Crear Edificio
     *
     * @OA\Post(
     * path="/edificios",
     * tags={"Edificios"},
     * summary="Registrar un nuevo edificio",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre"},
     * @OA\Property(property="nombre", type="string", example="Edificio Z", description="Nombre único del edificio")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Edificio creado exitosamente",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=5),
     * @OA\Property(property="nombre", type="string", example="Edificio Z")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación (nombre duplicado)"
     * )
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:edificios,nombre',
        ]);

        $edificio = Edificio::create($validatedData);

        return response()->json($edificio, 201);
    }

    /**
     * Ver Edificio
     *
     * Muestra un edificio específico y carga sus áreas asociadas.
     *
     * @OA\Get(
     * path="/edificios/{id}",
     * tags={"Edificios"},
     * summary="Obtener detalles de un edificio",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Datos del edificio con sus áreas",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="nombre", type="string", example="Edificio A"),
     * @OA\Property(
     * property="areas",
     * type="array",
     * @OA\Items(type="object", @OA\Property(property="area_nombre", type="string"))
     * )
     * )
     * ),
     * @OA\Response(response=404, description="Edificio no encontrado")
     * )
     */
    public function show(Edificio $edificio)
    {
        // Cargamos las áreas que pertenecen a este edificio
        return $edificio->load('areas');
    }

    /**
     * Actualizar Edificio
     *
     * @OA\Put(
     * path="/edificios/{id}",
     * tags={"Edificios"},
     * summary="Actualizar nombre de un edificio",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"nombre"},
     * @OA\Property(property="nombre", type="string", example="Edificio A (Renovado)")
     * )
     * ),
     * @OA\Response(response=200, description="Edificio actualizado"),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Edificio $edificio)
    {
        $validatedData = $request->validate([
            // Se asegura que el nombre sea único, ignorando su propio ID
            'nombre' => 'required|string|max:255|unique:edificios,nombre,' . $edificio->id,
        ]);

        $edificio->update($validatedData);

        return response()->json($edificio, 200);
    }

    /**
     * Eliminar Edificio
     *
     * @OA\Delete(
     * path="/edificios/{id}",
     * tags={"Edificios"},
     * summary="Eliminar un edificio",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=204,
     * description="Eliminado exitosamente"
     * ),
     * @OA\Response(
     * response=409,
     * description="Conflicto: No se puede eliminar porque tiene áreas u oficinas asociadas"
     * ),
     * @OA\Response(
     * response=500,
     * description="Error interno del servidor"
     * )
     * )
     */
    public function destroy(Edificio $edificio)
    {
        try {
            $edificio->delete();
            return response()->json(null, 204); // Éxito, sin contenido

        } catch (QueryException $e) {
            // Manejar error de llave foránea (si el edificio tiene áreas)
            return response()->json([
                'message' => 'No se puede eliminar el edificio porque tiene oficinas asociadas.'
            ], 409); // 409 Conflicto
        }
    }
}