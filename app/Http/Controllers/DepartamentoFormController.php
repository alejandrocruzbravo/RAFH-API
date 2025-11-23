<?php

namespace App\Http\Controllers;

use App\Models\Area; // Importamos el modelo Area
use App\Models\Resguardante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 * name="Formularios",
 * description="Endpoints para obtener datos auxiliares (listas) para llenar formularios"
 * )
 */
class DepartamentoFormController extends Controller
{
    /**
     * Obtener opciones para el formulario de Departamentos
     *
     * Devuelve los datos necesarios para poblar los selectores:
     * lista de Áreas disponibles y lista de Jefes de Departamento (responsables).
     *
     * @OA\Get(
     * path="/formularios/departamentos",
     * tags={"Formularios"},
     * summary="Obtener opciones para crear/editar Departamento",
     * operationId="getDepartamentoFormOptions",
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(
     * property="areas",
     * type="array",
     * description="Lista de todas las áreas",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="area_nombre", type="string", example="Subdirección Administrativa")
     * )
     * ),
     * @OA\Property(
     * property="responsables",
     * type="array",
     * description="Lista de resguardantes con rol de Jefe de Departamento",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=5),
     * @OA\Property(property="res_nombre", type="string", example="María González")
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
        // 1. Obtenemos la lista de áreas (solo ID y nombre)
        $areas = Area::select('id', 'area_nombre')
                     ->orderBy('area_nombre')
                     ->get();

        // 2. Obtenemos la lista de Jefes de Departamento
        $jefesDeDepartamento = DB::table('resguardantes')
            ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id') 
            ->where('roles.rol_nombre', 'Jefe de Departamento') 
            ->select('resguardantes.id', 'resguardantes.res_nombre') 
            ->get();

        // 3. Devolvemos un objeto JSON con todas las listas
        return response()->json([
            'areas' => $areas,
            'responsables' => $jefesDeDepartamento,
        ]);
    }
}