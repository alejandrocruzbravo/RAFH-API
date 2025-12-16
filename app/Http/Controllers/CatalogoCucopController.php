<?php

namespace App\Http\Controllers;

use App\Models\CatalogoCambCucop;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        // 1. Iniciar la consulta (Select * implícito)
        $query = CatalogoCambCucop::query();

        // 2. Aplicar la lógica de búsqueda (si se proporciona)
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function($q) use ($search) {
                $q->where('clave_cucop', 'LIKE', "%{$search}%")
                ->orWhere('camb', 'LIKE', "%{$search}%")
                ->orWhere('descripcion', 'LIKE', "%{$search}%")
                ->orWhere('partida_especifica', 'LIKE', "%{$search}%");
            });
        }

        // 3. Obtener los resultados ordenados y paginados
        $catalogo = $query->orderBy('id', 'desc')->paginate(15);

        return response()->json($catalogo);
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
        $validatedData = $request->validate([
            'clave_cucop' => 'required|integer|unique:catalogo_camb_cucop,clave_cucop', // Unique
            'partida_especifica' => 'nullable|string|max:255',
            'descripcion' => 'required|string',
            'camb' => 'nullable|string|max:255',
        ]);
        try {
            $registro = CatalogoCambCucop::create($validatedData);
            return response()->json($registro, 201);
        } catch (Throwable $e) {
            return response()->json(['error' => 'No se pudo crear el registro.', 'message' => $e->getMessage()], 500);
        }
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
            return response()->json(null, 204); 
        } catch (Throwable $e) {
            // Esto capturará excepciones como QueryException si hay FKs asociados
            return response()->json(['error' => 'No se pudo eliminar el registro.', 'message' => $e->getMessage()], 500);
        }
    }
}