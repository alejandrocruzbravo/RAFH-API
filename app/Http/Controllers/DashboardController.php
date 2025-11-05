<?php

namespace App\Http\Controllers;

// 1. Importa todos los modelos que necesitarás
use App\Models\Bien;
use App\Models\Area;
use App\Models\Gestor;
use App\Models\Resguardante;
use App\Models\Traspaso;
use App\Models\MovimientoBien;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Obtiene y devuelve las estadísticas para la vista general del dashboard.
     */
    public function index()
    {
        // --- Widget "Centro de trabajo" ---
        $statBienes = Bien::count();
        $statGestores = Gestor::count();
        $statAreas = Area::count();
        $statResguardantes = Resguardante::count();

        // --- Widget "Último bien registrado" ---
        $ultimoRegistro = MovimientoBien::where('movimiento_tipo', 'Registro')
            ->with('bien:id,bien_nombre') // Carga el nombre del bien asociado
            ->latest('movimiento_fecha')
            ->first();
        
        // 2. Filtra los datos
        $ultimoBienRegistradoFiltrado = $ultimoRegistro ? [
            'nombre' => $ultimoRegistro->bien->bien_nombre ?? 'N/A',
            'cantidad' => $ultimoRegistro->movimiento_cantidad,
        ] : null;

    // --- Widget "Última transferencia" ---
    $ultimaTrans = Traspaso::where('traspaso_estado', 'Completado')
        ->with([
            'bien:id,bien_nombre', // Selecciona PK y nombre
            'usuarioOrigen:id,usuario_nombre' // Selecciona PK y nombre
        ])
        ->latest('traspaso_fecha_solicitud')
        ->first();

    // Filtramos los datos que necesitamos
    $ultimaTransferenciaFiltrada = $ultimaTrans ? [
        'bien_nombre' => $ultimaTrans->bien->bien_nombre ?? 'N/A',
        'realizada_por' => $ultimaTrans->usuarioOrigen->usuario_nombre ?? 'N/A',
    ] : null;

        // --- Widget "Notificaciones (Solicitudes de transferencia)" ---
        $solicitudesPendientes = Traspaso::where('traspaso_estado', 'Pendiente')
        ->with([
            'bien:id,bien_nombre',
            'usuarioOrigen:id,usuario_nombre',
            'usuarioDestino:id,usuario_nombre'
        ])
        ->orderBy('traspaso_fecha_solicitud', 'asc')
        ->get()
        ->map(function ($traspaso) {
            return [
                'id_traspaso' => $traspaso->id, // El ID para los botones
                'bien_nombre' => $traspaso->bien->bien_nombre ?? 'N/A',
                'emisor' => $traspaso->usuarioOrigen->usuario_nombre ?? 'N/A',
                'receptor' => $traspaso->usuarioDestino->usuario_nombre ?? 'N/A',
            ];
        });

        $ultimosMovimientos = MovimientoBien::with([
            // Selecciona 'id' (la PK de bienes) y 'bien_nombre'
            'bien:id,bien_nombre', 
            // Selecciona 'id_usuario' (la PK de usuarios) y 'usuario_nombre'
            'usuarioAutorizado:id,usuario_nombre', 
            // Selecciona 'id_usuario' (la PK de usuarios) y 'usuario_nombre'
            'usuarioDestino:id,usuario_nombre',
            // Esta ya estaba correcta
            'departamento:id,dep_nombre'
        ])
        ->latest('movimiento_fecha')
        ->take(5)
        ->get()
        // Usamos map() para transformar la colección
        ->map(function ($movimiento) {
            return [
                'tipo' => $movimiento->movimiento_tipo,
                'bien_involucrado' => $movimiento->bien->bien_nombre ?? 'N/A',
                'gestor_encargado' => $movimiento->usuarioAutorizado->usuario_nombre ?? 'N/A',
                'resguardante_responsable' => $movimiento->usuarioDestino->usuario_nombre ?? 'N/A',
                'area' => $movimiento->departamento->dep_nombre ?? 'N/A',
            ];
        });

        // --- Widget "Próximo mantenimiento" ---
        $proxMant = Mantenimiento::where('mantenimiento_estado', 'Programado')
            ->where('fecha_programada', '>=', now())
            ->orderBy('fecha_programada', 'asc')
            ->with('bien:id,bien_nombre') // Selecciona PK y nombre
            ->first();

        // Filtramos y formateamos la fecha
        $proximoMantenimientoFiltrado = $proxMant ? [
            // Formatea la fecha como 12/07/25
            'fecha_programada' => $proxMant->fecha_programada->format('d/m/y'), 
            'para_bien_nombre' => $proxMant->bien->bien_nombre ?? 'N/A',
        ] : null;

        // 2. Devuelve todo en una sola respuesta JSON
        return response()->json([
            'stats' => [
                'bienes_registrados' => $statBienes,
                'gestores_asignados' => $statGestores,
                'areas_asociadas' => $statAreas,
                'resguardantes_registrados' => $statResguardantes,
            ],
            'ultimo_bien_registrado' => $ultimoBienRegistradoFiltrado,
            'ultima_transferencia' => $ultimaTransferenciaFiltrada,
            'notificaciones' =>  $solicitudesPendientes,
            'ultimos_movimientos' => $ultimosMovimientos,
            'proximo_mantenimiento' => $proximoMantenimientoFiltrado,
        ]);
    }
}