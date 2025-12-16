<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Resguardo;
use App\Models\Bien;

/**
 * @OA\Tag(
 * 
 * )
 */
class ResguardoController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/resguardos",
     * summary="Gestionar asignación o liberación de resguardos",
     * description="Este endpoint funciona como un despachador. Dependiendo del campo 'accion', asigna bienes a un usuario ('create') o libera los bienes de su resguardo actual ('release').",
     * tags={"Resguardos"},
     * @OA\RequestBody(
     * required=true,
     * description="Datos para procesar el resguardo",
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(
     * required={"bienes_ids"},
     * @OA\Property(
     * property="accion",
     * type="string",
     * enum={"create", "release"},
     * default="create",
     * description="Acción a realizar. Si se omite, por defecto es 'create' (Asignación). Use 'release' para liberar bienes."
     * ),
     * @OA\Property(
     * property="bienes_ids",
     * type="array",
     * description="Lista de IDs de los bienes a asignar o liberar.",
     * @OA\Items(
     * type="integer",
     * example=10
     * )
     * ),
     * @OA\Property(
     * property="id_resguardante",
     * type="integer",
     * description="ID del empleado que recibirá los bienes. **Requerido** solo si la acción es 'create'."
     * ),
     * example={
     * "accion": "create",
     * "id_resguardante": 45,
     * "bienes_ids": {101, 102, 105}
     * }
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Bienes asignados exitosamente (Acción: create)",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Bienes asignados correctamente"),
     * @OA\Property(property="count", type="integer", example=3)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Bienes liberados exitosamente (Acción: release)",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Bienes liberados correctamente"),
     * @OA\Property(property="count", type="integer", example=2)
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación (Faltan campos o IDs inválidos)",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="The id resguardante field is required."),
     * @OA\Property(property="errors", type="object")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Error interno del servidor al procesar la transacción",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Error al asignar los resguardos"),
     * @OA\Property(property="error", type="string", example="Detalle del error SQL o excepción")
     * )
     * )
     * )
     */
    public function store(Request $request)
    {
        $accion = $request->input('accion', 'create');

        if ($accion === 'release') {
            return $this->procesarLiberacion($request);
        }

        return $this->procesarAsignacion($request);
    }


    private function procesarAsignacion(Request $request)
    {
        // Validación para Asignación
        $request->validate([
            'id_resguardante' => 'required|exists:resguardantes,id',
            'bienes_ids'      => 'required|array|min:1',
            'bienes_ids.*'    => 'exists:bienes,id',
        ]);

        $resguardanteId = $request->input('id_resguardante');
        $bienesIds = $request->input('bienes_ids');
        $fechaHoy = Carbon::now();

        try {
            DB::beginTransaction();

            $bienes = Bien::with('oficina')->whereIn('id', $bienesIds)->get();

            foreach ($bienes as $bien) {
                // Obtener depto
                $idDepartamento = $bien->oficina ? $bien->oficina->id_departamento : null;

                // Crear historial en 'resguardos'
                Resguardo::create([
                    'resguardo_id_bien'         => $bien->id,
                    'resguardo_id_resguardante' => $resguardanteId,
                    'resguardo_fecha_asignacion'=> $fechaHoy,
                    'resguardo_id_dep'          => $idDepartamento,
                ]);

                // Actualizar estado actual del bien
                $bien->update([
                    'id_resguardante' => $resguardanteId
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Bienes asignados correctamente',
                'count' => count($bienesIds)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al asignar los resguardos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function procesarLiberacion(Request $request)
    {
        // Validación para Liberación (Solo necesitamos los IDs de los bienes)
        $request->validate([
            'bienes_ids'   => 'required|array|min:1',
            'bienes_ids.*' => 'exists:bienes,id',
        ]);

        $bienesIds = $request->input('bienes_ids');
        try {
            DB::beginTransaction();
            Bien::whereIn('id', $bienesIds)->update([
                'id_resguardante' => null
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Bienes liberados correctamente',
                'count' => count($bienesIds)
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al liberar los resguardos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}