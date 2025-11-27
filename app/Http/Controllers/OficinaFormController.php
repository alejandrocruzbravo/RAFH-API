<?php

namespace App\Http\Controllers;

use App\Models\Edificio; // Importamos Edificio
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 * name="Formularios",
 * description="Endpoints para obtener datos auxiliares (listas) para llenar formularios"
 * )
 */
class OficinaFormController extends Controller
{
    /**
     * Obtener opciones para el formulario de Oficinas
     *
     * Devuelve la lista de edificios disponibles para poblar el selector en la creación/edición de oficinas.
     *
     * @OA\Get(
     * path="/formularios/oficinas",
     * tags={"Formularios"},
     * summary="Obtener opciones para crear/editar Oficina",
     * operationId="getOficinaFormOptions",
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(
     * property="edificios",
     * type="array",
     * description="Lista de todos los edificios",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="nombre", type="string", example="Edificio A")
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Error del servidor"
     * )
     * )
     */
    public function __invoke(Request $request)
    {
        // 1. Obtenemos la lista de edificios (solo ID y nombre)
        $edificios = Edificio::select('id', 'nombre')
                             ->orderBy('nombre')
                             ->get();

        // 2. Devolvemos un objeto JSON con todas las listas
        return response()->json([
            'edificios' => $edificios,
        ]);
    }
}