<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Resguardo;
use App\Models\Bien;

class ResguardoController extends Controller
{
    /**
     * Store (Dispatcher): Decide si asignar o liberar basándose en la bandera 'accion'.
     */
    public function store(Request $request)
    {
        // Leemos la bandera de acción (por defecto asumimos creación si no viene)
        $accion = $request->input('accion', 'create');

        if ($accion === 'release') {
            return $this->procesarLiberacion($request);
        }

        // Si no es release, asumimos que es una asignación nueva
        return $this->procesarAsignacion($request);
    }

    /**
     * Lógica para ASIGNAR bienes (Tu código original refactorizado)
     */
    private function procesarAsignacion(Request $request)
    {
        // 1. Validación para Asignación
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
                // A. Obtener depto
                $idDepartamento = $bien->oficina ? $bien->oficina->id_departamento : null;

                // B. Crear historial en 'resguardos'
                Resguardo::create([
                    'resguardo_id_bien'         => $bien->id,
                    'resguardo_id_resguardante' => $resguardanteId,
                    'resguardo_fecha_asignacion'=> $fechaHoy,
                    'resguardo_id_dep'          => $idDepartamento,
                ]);

                // C. Actualizar estado actual del bien
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

    /**
     * Lógica para LIBERAR (Bulk Delete / Release)
     */
    private function procesarLiberacion(Request $request)
    {
        // 1. Validación para Liberación (Solo necesitamos los IDs de los bienes)
        $request->validate([
            'bienes_ids'   => 'required|array|min:1',
            'bienes_ids.*' => 'exists:bienes,id',
        ]);

        $bienesIds = $request->input('bienes_ids');
        // Opcional: Si quieres validar que pertenezcan a cierto resguardante antes de borrar
        // $resguardanteId = $request->input('id_resguardante'); 

        try {
            DB::beginTransaction();

            // PASO A: Liberar los bienes (Quitar el resguardante actual)
            // Esto es masivo y muy rápido
            Bien::whereIn('id', $bienesIds)->update([
                'id_resguardante' => null
            ]);

            // PASO B: Gestionar el historial en la tabla 'resguardos'
            // OPCIÓN 1: Si "Liberar" significa que se equivocaron y hay que BORRAR el registro:
            // (Asumimos que borramos el último resguardo activo de estos bienes)
            /* Resguardo::whereIn('resguardo_id_bien', $bienesIds)
                ->orderBy('resguardo_fecha_asignacion', 'desc') // Cuidado con borrar históricos viejos
                ->delete(); 
            */

            // OPCIÓN 2 (Recomendada para auditoría): NO borrar, sino marcar fecha de baja.
            // Si tu tabla 'resguardos' tiene una columna 'resguardo_fecha_baja' o 'fecha_devolucion':
            /*
            Resguardo::whereIn('resguardo_id_bien', $bienesIds)
                ->whereNull('resguardo_fecha_baja') // Solo los que están activos
                ->update(['resguardo_fecha_baja' => Carbon::now()]);
            */

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