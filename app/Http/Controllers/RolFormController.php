<?php

namespace App\Http\Controllers;

use App\Models\Rol; // <-- Importa el modelo Rol
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 * name="Formularios",
 * description="Endpoints para obtener datos auxiliares (listas) para llenar formularios"
 * )
 */
class RolFormController extends Controller
{
    /**
     * Obtener lista de roles
     *
     * Devuelve una lista de todos los roles disponibles en el sistema.
     * Se utiliza para poblar selectores en formularios de creación/edición de usuarios.
     *
     * @OA\Get(
     * path="/formularios/roles",
     * tags={"Formularios"},
     * summary="Obtener lista completa de roles",
     * operationId="getRolFormOptions",
     * @OA\Response(
     * response=200,
     * description="Lista de roles obtenida exitosamente",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="rol_nombre", type="string", example="Administrador")
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
        // Optimizamos la consulta para obtener solo el ID y el nombre
        $roles = Rol::select('id', 'rol_nombre')
                    ->orderBy('rol_nombre') // Ordena alfabéticamente
                    ->get();
        
        // Devolvemos la colección como un array JSON
        return response()->json($roles);
    }
}