<?php

namespace App\Http\Controllers;

use App\Models\Edificio;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 * name="Formularios",
 * description="Endpoints para obtener datos auxiliares (listas) para llenar formularios"
 * )
 */
class AreaFormController extends Controller
{
    /**
     * Obtener opciones para el formulario de Áreas
     *
     * Devuelve los datos necesarios para poblar los selectores de la interfaz:
     * lista de Subdirectores (responsables), edificios y departamentos disponibles.
     *
     * @OA\Get(
     * path="/formularios/areas",
     * tags={"Formularios"},
     * summary="Obtener opciones para crear/editar Área",
     * operationId="getAreaFormOptions",
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(
     * property="responsables",
     * type="array",
     * description="Lista de resguardantes con rol de Subdirector",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="res_nombre", type="string", example="Carlos Subdirector")
     * )
     * ),
     * @OA\Property(
     * property="edificios",
     * type="array",
     * description="Lista de todos los edificios",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=5),
     * @OA\Property(property="nombre", type="string", example="Edificio A")
     * )
     * ),
     * @OA\Property(
     * property="departamentos",
     * type="array",
     * description="Lista de todos los departamentos",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=10),
     * @OA\Property(property="dep_nombre", type="string", example="Recursos Humanos")
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
    public function getOptions()
    {
 /*       // 1. Obtener la lista de subdirectores
        $subdirectores = DB::table('resguardantes')
            ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id')
            ->where('roles.rol_nombre', 'Subdirector') 
            ->select('resguardantes.id', 'resguardantes.res_nombre') 
            ->get();
*/
        // 2. Obtener todos los edificios
        $edificios = Edificio::select('id', 'nombre')->get();

        // 3. Obtener todos los departamentos
        $departamentos = Departamento::select('id', 'dep_nombre')->get();

        // 4. Devolver todo en una sola respuesta JSON
        return response()->json([
            'edificios' => $edificios,
            'departamentos' => $departamentos,
        ]);
    }
}