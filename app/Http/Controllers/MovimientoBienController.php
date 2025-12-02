<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBien;
use Illuminate\Http\Request;

class MovimientoBienController extends Controller
{
    public function index(Request $request)
    {
        // Iniciamos la consulta cargando relaciones para evitar N+1
        $query = MovimientoBien::with([
            'bien.oficina',      // Para saber origen administrativo
            'departamento',      // Para saber destino físico
            'usuarioOrigen'      // Para saber QUIÉN hizo el movimiento
        ]);

        // --- FILTROS ---

        // 1. Búsqueda General (Nombre bien, Código bien, Nombre Usuario)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Buscar por datos del Bien
                $q->whereHas('bien', function($bq) use ($search) {
                    $bq->where('bien_descripcion', 'ILIKE', "%{$search}%")
                       ->orWhere('bien_codigo', 'ILIKE', "%{$search}%");
                })
                // O buscar por nombre del Usuario que movió
                ->orWhereHas('usuarioOrigen', function($uq) use ($search) {
                    $uq->where('usuario_nombre', 'ILIKE', "%{$search}%");
                });
            });
        }

        // 2. Filtro por Tipo (Entrada, Salida, Traslado, etc.)
        if ($request->has('tipo') && $request->tipo) {
            $query->where('movimiento_tipo', $request->tipo);
        }

        // 3. Filtro por Fechas (Desde - Hasta)
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('movimiento_fecha', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('movimiento_fecha', '<=', $request->date_to);
        }

        // Ordenamos por fecha descendente (lo más nuevo primero)
        return response()->json($query->orderBy('movimiento_fecha', 'desc')->paginate(20));
    }
}