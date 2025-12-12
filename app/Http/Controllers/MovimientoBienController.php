<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBien;
use Illuminate\Http\Request;


/**
 * @OA\Tag(
 * name="Movimientos",
 * description="Endpoints para obtener los movimientos del bien"
 * )
 */
class MovimientoBienController extends Controller
{
    /**
     * @OA\Get(
     * path="/movimientos",
     * summary="Listar historial de movimientos",
     * description="Obtiene el historial de movimientos (altas, traslados, bajas) con filtros avanzados de búsqueda, tipo y fecha.",
     * tags={"Movimientos"},
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Búsqueda general: Coincidencia en descripción del bien, código del bien o nombre del usuario que realizó el movimiento.",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="tipo",
     * in="query",
     * description="Filtrar por tipo de movimiento (ej. 'ALTA', 'MOVIMIENTO', 'BAJA')",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="date_from",
     * in="query",
     * description="Fecha inicial (YYYY-MM-DD) para filtrar rango",
     * required=false,
     * @OA\Schema(type="string", format="date")
     * ),
     * @OA\Parameter(
     * name="date_to",
     * in="query",
     * description="Fecha final (YYYY-MM-DD) para filtrar rango",
     * required=false,
     * @OA\Schema(type="string", format="date")
     * ),
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Número de página para paginación",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de movimientos",
     * @OA\JsonContent(
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="data", type="array", @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=101),
     * @OA\Property(property="movimiento_tipo", type="string", example="MOVIMIENTO"),
     * @OA\Property(property="movimiento_fecha", type="string", format="date-time", example="2025-10-20T14:30:00.000000Z"),
     * @OA\Property(property="movimiento_observaciones", type="string", example="Traslado solicitado por cambio de oficina"),
     * @OA\Property(
     * property="bien",
     * type="object",
     * description="Información del bien afectado",
     * @OA\Property(property="id", type="integer", example=50),
     * @OA\Property(property="bien_codigo", type="string", example="PC-004"),
     * @OA\Property(property="bien_descripcion", type="string", example="Monitor Dell 24"),
     * @OA\Property(property="oficina", type="object", description="Oficina administrativa original", nullable=true,
     * @OA\Property(property="nombre", type="string", example="Dirección de Finanzas")
     * )
     * ),
     * @OA\Property(
     * property="usuario_origen",
     * type="object",
     * description="Usuario que realizó la acción",
     * nullable=true,
     * @OA\Property(property="id", type="integer", example=5),
     * @OA\Property(property="usuario_nombre", type="string", example="Admin Sistema")
     * ),
     * @OA\Property(
     * property="departamento",
     * type="object",
     * description="Destino físico (Departamento/Ubicación)",
     * nullable=true,
     * @OA\Property(property="id", type="integer", example=12),
     * @OA\Property(property="dep_nombre", type="string", example="Almacén General")
     * )
     * )),
     * @OA\Property(property="total", type="integer", example=150),
     * @OA\Property(property="per_page", type="integer", example=20)
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        // Iniciamos la consulta cargando relaciones para evitar N+1
        $query = MovimientoBien::with([
            'bien.oficina',      // Para saber origen administrativo
            'departamento',      // Para saber destino físico
            'usuarioOrigen'      // Para saber quién hizo el movimiento
        ]);

        // Aplicamos los filtros

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