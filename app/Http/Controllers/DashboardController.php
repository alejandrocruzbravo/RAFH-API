<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Area;
use App\Models\Gestor;
use App\Models\Resguardante;
use App\Models\Traspaso;
use App\Models\MovimientoBien;
use App\Models\Departamento;
use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 * name="Dashboard",
 * description="Endpoints para obtener estadísticas y datos resumidos del sistema"
 * )
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     * path="/dashboard",
     * summary="Obtener métricas y datos del Dashboard",
     * description="Retorna todos los contadores, últimas actividades, notificaciones pendientes y datos para gráficas en una sola llamada.",
     * tags={"Dashboard"},
     * @OA\Response(
     * response=200,
     * description="Datos cargados correctamente",
     * @OA\JsonContent(
     * type="object",
     * * @OA\Property(
     * property="stats",
     * type="object",
     * description="Contadores generales para los widgets superiores",
     * @OA\Property(property="bienes_registrados", type="integer", example=1250),
     * @OA\Property(property="gestores_asignados", type="integer", example=5),
     * @OA\Property(property="areas_asociadas", type="integer", example=8),
     * @OA\Property(property="resguardantes_registrados", type="integer", example=45),
     * @OA\Property(property="departamentos_totales", type="integer", example=12),
     * @OA\Property(property="oficinas_totales", type="integer", example=30)
     * ),
     * * @OA\Property(
     * property="ultimo_bien_registrado",
     * type="object",
     * nullable=true,
     * description="Información del último lote o bien dado de alta",
     * @OA\Property(property="nombre", type="string", example="Laptops Dell Latitude"),
     * @OA\Property(property="cantidad", type="integer", example=10)
     * ),
     * * @OA\Property(
     * property="ultima_transferencia",
     * type="object",
     * nullable=true,
     * description="Último traspaso aprobado",
     * @OA\Property(property="bien_nombre", type="string", example="Proyector Epson"),
     * @OA\Property(property="realizada_por", type="string", example="Juan Pérez")
     * ),
     * * @OA\Property(
     * property="notificaciones",
     * type="object",
     * nullable=true,
     * description="Última solicitud de traspaso pendiente (para alerta)",
     * @OA\Property(property="id_traspaso", type="integer", example=205),
     * @OA\Property(property="bien_nombre", type="string", example="Monitor Samsung"),
     * @OA\Property(property="emisor", type="string", example="Ana García"),
     * @OA\Property(property="receptor", type="string", example="Carlos López")
     * ),
     * * @OA\Property(
     * property="ultimos_movimientos",
     * type="array",
     * description="Lista de los 5 movimientos más recientes",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="tipo", type="string", example="MOVIMIENTO"),
     * @OA\Property(property="bien_involucrado", type="string", example="Silla Ejecutiva"),
     * @OA\Property(property="gestor_encargado", type="string", example="Admin"),
     * @OA\Property(property="resguardante_responsable", type="string", example="Roberto Gómez"),
     * @OA\Property(property="area", type="string", example="Recursos Humanos")
     * )
     * ),
     * * @OA\Property(
     * property="estados_bienes",
     * type="array",
     * description="Datos para la gráfica de pastel (Totales por estado)",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="bien_estado", type="string", example="Activo"),
     * @OA\Property(property="total", type="integer", example=850)
     * )
     * )
     * )
     * )
     * )
     */
    public function index()
    {
        // --- Widget "Centro de trabajo" ---
        $statBienes = Bien::count();
        $statGestores = Gestor::count();
        $statAreas = Area::count();
        $statResguardantes = Resguardante::count();
        
        $statDepartamentos = Departamento::count();
        $statOficinas = Oficina::count();

        // --- Widget "Último bien registrado" ---
        $ultimoRegistro = MovimientoBien::where('movimiento_tipo', 'ALTA')
            ->with('bien:id,bien_descripcion')
            ->latest('movimiento_fecha')
            ->first();
        
        $ultimoBienRegistradoFiltrado = $ultimoRegistro ? [
            'nombre' => $ultimoRegistro->bien->bien_descripcion ?? 'N/A',
            'cantidad' => $ultimoRegistro->movimiento_cantidad,
        ] : null;

        // --- Widget "Última transferencia" ---
        $ultimaTrans = Traspaso::where('traspaso_estado', 'Aprobada')
            ->with([
                'bien:id,bien_descripcion',
                'resguardanteOrigen.usuario:id,usuario_nombre'
            ])
            ->latest('traspaso_fecha_solicitud')
            ->first();

        $ultimaTransferenciaFiltrada = $ultimaTrans ? [
            'bien_nombre' => $ultimaTrans->bien->bien_descripcion ?? 'N/A',
            // Accedemos a resguardanteOrigen -> usuario -> nombre
            'realizada_por' => $ultimaTrans->resguardanteOrigen->usuario->usuario_nombre ?? 'N/A',
        ] : null;

        // --- Widget "Notificaciones" ---
        $ultimaSolicitud = Traspaso::where('traspaso_estado', 'Pendiente')
            ->with([
                'bien:id,bien_descripcion', // O bien_nombre si así se llama en tu BD
                'resguardanteOrigen.usuario:id,usuario_nombre',
                'resguardanteDestino.usuario:id,usuario_nombre'
            ])
            ->latest('traspaso_fecha_solicitud')
            ->first();

        $solicitudWidget = null;
        if ($ultimaSolicitud) {
            $solicitudWidget = [
                'id_traspaso' => $ultimaSolicitud->id,
                'bien_nombre' => $ultimaSolicitud->bien->bien_descripcion ?? 'N/A',
                // Acceso seguro a relaciones profundas
                'emisor'      => $ultimaSolicitud->resguardanteOrigen->usuario->usuario_nombre ?? 'Desconocido',
                'receptor'    => $ultimaSolicitud->resguardanteDestino->usuario->usuario_nombre ?? 'Desconocido',
            ];
        }

        // --- Widget "Últimos Movimientos" ---
        $ultimosMovimientos = MovimientoBien::with([
                'bien:id,bien_descripcion', 
                'usuarioAutorizado:id,usuario_nombre', 
                'usuarioDestino:id,usuario_nombre',
                'departamento:id,dep_nombre'
            ])
            ->latest('movimiento_fecha')
            ->take(5)
            ->get()
            ->map(function ($movimiento) {
                return [
                    'tipo' => $movimiento->movimiento_tipo,
                    'bien_involucrado' => $movimiento->bien->bien_descripcion ?? 'N/A',
                    'gestor_encargado' => $movimiento->usuarioAutorizado->usuario_nombre ?? 'N/A',
                    'resguardante_responsable' => $movimiento->usuarioDestino->usuario_nombre ?? 'N/A',
                    'area' => $movimiento->departamento->dep_nombre ?? 'N/A',
                ];
            });

        // --- Gráfica de Pastel ---
        $estadosBienes = Bien::select('bien_estado', DB::raw('count(*) as total'))
                                ->groupBy('bien_estado')
                                ->get();

        return response()->json([
            'stats' => [
                'bienes_registrados' => $statBienes,
                'gestores_asignados' => $statGestores,
                'areas_asociadas' => $statAreas,
                'resguardantes_registrados' => $statResguardantes,
                'departamentos_totales' => $statDepartamentos,
                'oficinas_totales' => $statOficinas,
            ],
            'ultimo_bien_registrado' => $ultimoBienRegistradoFiltrado,
            'ultima_transferencia' => $ultimaTransferenciaFiltrada,
            'notificaciones' => $solicitudWidget, 
            'ultimos_movimientos' => $ultimosMovimientos,
            'estados_bienes' => $estadosBienes,
        ]);
    }
}