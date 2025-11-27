<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Departamento;
use App\Models\Rol;
use App\Models\Oficina;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 * name="Formularios",
 * description="Endpoints para obtener datos auxiliares (listas) para llenar formularios"
 * )
 */
class ResguardanteFormController extends Controller
{
    /**
     * Obtener opciones para el formulario de Resguardantes
     *
     * Devuelve los datos necesarios para poblar los selectores en la creación/edición de resguardantes:
     * departamentos, roles válidos y oficinas disponibles.
     *
     * @OA\Get(
     * path="/formularios/resguardantes",
     * tags={"Formularios"},
     * summary="Obtener opciones para crear/editar Resguardante",
     * operationId="getResguardanteFormOptions",
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(
     * property="departamentos",
     * type="array",
     * description="Lista de departamentos",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="dep_nombre", type="string", example="Recursos Humanos")
     * )
     * ),
     * @OA\Property(
     * property="roles",
     * type="array",
     * description="Lista de roles permitidos para resguardantes (ID >= 3)",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=3),
     * @OA\Property(property="rol_nombre", type="string", example="Jefe de Departamento")
     * )
     * ),
     * @OA\Property(
     * property="oficinas",
     * type="array",
     * description="Lista de oficinas con su edificio",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=10),
     * @OA\Property(property="nombre", type="string", example="Oficina 101"),
     * @OA\Property(property="id_departamento", type="integer", example=1),
     * @OA\Property(
     * property="edificio",
     * type="object",
     * @OA\Property(property="id", type="integer", example=5),
     * @OA\Property(property="nombre", type="string", example="Edificio A")
     * )
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
        // 1. Obtener la lista de departamentos
        $departamentos = Departamento::select('id', 'dep_nombre')
                                     ->orderBy('dep_nombre')
                                     ->get();
        
        // 2. Obtener los roles con ID >= 3
        $roles = Rol::select('id', 'rol_nombre')
                    ->where('id', '>=', 3)
                    ->orderBy('rol_nombre')
                    ->get();

        // 3. Obtener oficinas con su edificio
        $oficinas = Oficina::select('id', 'nombre', 'id_departamento') 
                           ->with('edificio:id,nombre') 
                           ->orderBy('nombre')
                           ->get();

        // 4. Devolver todo en una sola respuesta JSON
        return response()->json([
            'departamentos' => $departamentos,
            'roles' => $roles,
            'oficinas' => $oficinas,
        ]);
    }
}