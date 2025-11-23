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
     * Obtener Estadísticas Generales
     *
     * Retorna un resumen completo para el panel de control: conteos totales, 
     * últimos registros, notificaciones pendientes y datos para gráficas.
     *
     * @OA\Get(
     * path="/dashboard/stats",
     * tags={"Dashboard"},
     * summary="Obtener estadísticas del Dashboard",
     * @OA\Response(
     * response=200,
     * description="Datos del dashboard recuperados exitosamente",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(
     * property="stats",
     * type="object",
     * description="Contadores generales del sistema",
     * @OA\Property(property="bienes_registrados", type="integer", example=20213),
     * @OA\Property(property="gestores_asignados", type="integer", example=5),
     * @OA\Property(property="areas_asociadas", type="integer", example=8),
     * @OA\Property(property="resguardantes_registrados", type="integer", example=150),
     * @OA\Property(property="departamentos_totales", type="integer", example=12),
     * @OA\Property(property="oficinas_totales", type="integer", example=45)
     * ),
     * @OA\Property(
     * property="ultimo_bien_registrado",
     * type="object",
     * nullable=true,
     * description="Información breve del último bien dado de alta",
     * @OA\Property(property="nombre", type="string", example="Laptop HP ProBook"),
     * @OA\Property(property="cantidad", type="integer", example=10)
     * ),
     * @OA\Property(
     * property="ultima_transferencia",
     * type="object",
     * nullable=true,
     * description="Información breve del último traspaso completado",
     * @OA\Property(property="bien_nombre", type="string", example="Silla Ejecutiva"),
     * @OA\Property(property="realizada_por", type="string", example="Juan Pérez")
     * ),
     * @OA\Property(
     * property="notificaciones",
     * type="object",
     * nullable=true,
     * description="La solicitud de traspaso pendiente más reciente (para el widget de alerta)",
     * @OA\Property(property="id_traspaso", type="integer", example=35),
     * @OA\Property(property="bien_nombre", type="string", example="Monitor Dell"),
     * @OA\Property(property="emisor", type="string", example="Ana López"),
     * @OA\Property(property="receptor", type="string", example="Carlos Ruiz")
     * ),
     * @OA\Property(
     * property="ultimos_movimientos",
     * type="array",
     * description="Lista de los 5 movimientos más recientes",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="tipo", type="string", example="Asignación"),
     * @OA\Property(property="bien_involucrado", type="string", example="Teclado USB"),
     * @OA\Property(property="gestor_encargado", type="string", example="Admin"),
     * @OA\Property(property="resguardante_responsable", type="string", example="Pedro Gomez"),
     * @OA\Property(property="area", type="string", example="Sistemas")
     * )
     * ),
     * @OA\Property(
     * property="estados_bienes",
     * type="array",
     * description="Datos para la gráfica de pastel (conteo por estado)",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="bien_estado", type="string", example="Bueno"),
     * @OA\Property(property="total", type="integer", example=150)
     * )
     * )
     * )
     * ),
     * @OA\Response(response=500, description="Error del servidor")
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
        $ultimoRegistro = MovimientoBien::where('movimiento_tipo', 'Registro')
            ->with('bien:id,bien_descripcion')
            ->latest('movimiento_fecha')
            ->first();
        
        $ultimoBienRegistradoFiltrado = $ultimoRegistro ? [
            'nombre' => $ultimoRegistro->bien->bien_descripcion ?? 'N/A',
            'cantidad' => $ultimoRegistro->movimiento_cantidad,
        ] : null;

        // --- Widget "Última transferencia" ---
        $ultimaTrans = Traspaso::where('traspaso_estado', 'Completado')
            ->with([
                'bien:id,bien_nombre',
                'usuarioOrigen:id,usuario_nombre'
            ])
            ->latest('traspaso_fecha_solicitud')
            ->first();

        $ultimaTransferenciaFiltrada = $ultimaTrans ? [
            'bien_nombre' => $ultimaTrans->bien->bien_nombre ?? 'N/A',
            'realizada_por' => $ultimaTrans->usuarioOrigen->usuario_nombre ?? 'N/A',
        ] : null;

        // --- Widget "Notificaciones" ---
        $ultimaSolicitud = Traspaso::where('traspaso_estado', 'Pendiente')
            ->with([
                'bien:id,bien_nombre',
                'usuarioOrigen:id,usuario_nombre',
                'usuarioDestino:id,usuario_nombre'
            ])
            ->latest('traspaso_fecha_solicitud') 
            ->first();

        $solicitudWidget = null;
        if ($ultimaSolicitud) {
            $solicitudWidget = [
                'id_traspaso' => $ultimaSolicitud->id,
                'bien_nombre' => $ultimaSolicitud->bien->bien_nombre ?? 'N/A',
                'emisor' => $ultimaSolicitud->usuarioOrigen->usuario_nombre ?? 'N/A',
                'receptor' => $ultimaSolicitud->usuarioDestino->usuario_nombre ?? 'N/A',
            ];
        }

        // --- Widget "Últimos Movimientos" ---
        $ultimosMovimientos = MovimientoBien::with([
                'bien:id,bien_nombre', 
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
                    'bien_involucrado' => $movimiento->bien->bien_nombre ?? 'N/A',
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