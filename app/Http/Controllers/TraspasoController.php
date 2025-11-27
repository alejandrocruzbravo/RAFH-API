<?php

namespace App\Http\Controllers;

use App\Models\Traspaso;
use Illuminate\Http\Request;
use App\Events\SolicitudTraspasoCreada;
use App\Events\SolicitudTraspasoActualizada;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * @OA\Tag(
 * name="Traspasos",
 * description="Endpoints para la gestión de solicitudes de traspaso de bienes"
 * )
 */
class TraspasoController extends Controller
{
    /**
     * Listar Solicitudes de Traspaso
     *
     * Obtiene una lista paginada de las solicitudes. Permite filtrar por estado y buscar por nombre del bien o solicitante.
     *
     * @OA\Get(
     * path="/traspasos",
     * tags={"Traspasos"},
     * summary="Listar y filtrar traspasos",
     * @OA\Parameter(
     * name="estado",
     * in="query",
     * description="Filtrar por estado (ej. Pendiente, Aprobado, Rechazado)",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Buscar por nombre del bien o nombre del usuario solicitante",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Número de página",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de solicitudes",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="data", type="array", @OA\Items(type="object")),
     * @OA\Property(property="current_page", type="integer"),
     * @OA\Property(property="total", type="integer")
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        $query = Traspaso::with([
            // Para la columna 'Solicitante'
            'usuarioOrigen:id,usuario_nombre', 
            // Para la columna 'Descripción' (ej. "Transferencia de Laptop")
            'bien:id,bien_nombre' // Asumo que la tabla 'bienes' tiene 'nombre'
        ]);

        // --- Para el filtro de "Todos los estados" ---
        if ($request->filled('estado')) {
            $query->where('traspaso_estado', $request->input('estado'));
        }

        // --- Para la "Buscar solicitud" ---
        // (Asumimos que busca por nombre del bien o nombre del solicitante)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('bien', function($subQuery) use ($searchTerm) {
                    $subQuery->where('nombre', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('usuarioOrigen', function($subQuery) use ($searchTerm) {
                    $subQuery->where('usuario_nombre', 'like', "%{$searchTerm}%");
                });
            });
        }

        // Ordena por la más reciente primero
        $solicitudes = $query->latest('traspaso_fecha_solicitud')->paginate(10);

        return $solicitudes;
    }

    /**
     * Crear Solicitud de Traspaso
     *
     * Crea una nueva solicitud de traspaso y emite un evento WebSocket en tiempo real.
     *
     * @OA\Post(
     * path="/traspasos",
     * tags={"Traspasos"},
     * summary="Crear nueva solicitud",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"traspaso_id_bien", "traspaso_id_usuario_destino"},
     * @OA\Property(property="traspaso_id_bien", type="integer", description="ID del bien a traspasar", example=1),
     * @OA\Property(property="traspaso_id_usuario_destino", type="integer", description="ID del usuario receptor", example=5),
     * @OA\Property(property="traspaso_observaciones", type="string", description="Motivo o detalles", example="Cambio de oficina")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Solicitud creada exitosamente",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(response=422, description="Error de validación"),
     * @OA\Response(response=500, description="Error del servidor o transacción fallida")
     * )
     */
    public function store(Request $request)
    {
        // 1. Validación de los datos que envía el resguardante
        $validatedData = $request->validate([
            'traspaso_id_bien' => 'required|integer|exists:bienes,id',
            'traspaso_id_usuario_destino' => 'required|integer|exists:usuarios,id|not_in:'.Auth::id(),
            'traspaso_observaciones' => 'nullable|string|max:1000',
        ]);

        $traspaso = null;

        try {
            // 2. Usamos una transacción para asegurar la integridad
            DB::beginTransaction();

            // 3. Creamos el registro del Traspaso
            $traspaso = Traspaso::create([
                'traspaso_id_bien' => $validatedData['traspaso_id_bien'],
                'traspaso_id_usuario_destino' => $validatedData['traspaso_id_usuario_destino'],
                'traspaso_observaciones' => $validatedData['traspaso_observaciones'] ?? null,
                
                // --- Datos que el backend asigna ---
                'traspaso_id_usuario_origen' => Auth::id(), // El usuario que hace la solicitud
                'traspaso_fecha_solicitud' => now(),
                'traspaso_estado' => 'Pendiente', // Estado inicial
            ]);

            // 4. Carga las relaciones necesarias para el evento
            $traspaso->load('bien:id,bien_nombre', 'usuarioOrigen:id,usuario_nombre', 'usuarioDestino:id,usuario_nombre');
            broadcast(new SolicitudTraspasoCreada($traspaso));

            // 6. Confirma la transacción
            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear la solicitud de traspaso.',
                'message' => $e->getMessage()
            ], 500);
        }

        // 7. Devuelve la solicitud creada
        return response()->json($traspaso, 201);
    }

    /**
     * Ver Detalles de Solicitud
     *
     * Muestra la información completa de una solicitud, incluyendo detalles profundos del origen y destino (Resguardante, Depto, Oficina).
     *
     * @OA\Get(
     * path="/traspasos/{id}",
     * tags={"Traspasos"},
     * summary="Obtener detalles de una solicitud",
     * @OA\Parameter(name="id", in="path", required=true, description="ID del traspaso", @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Datos del traspaso con relaciones",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(response=404, description="Solicitud no encontrada")
     * )
     */
    public function show(Traspaso $traspaso)
    {
        //¡IMPORTANTE! Omitimos cargar 'bien' como solicitaste.
        
        $traspaso->load([
            
            // --- Cargar datos de ORIGEN ---
            // 'usuarioOrigen.resguardante.departamento.area'
            // 'usuarioOrigen.resguardante.oficina.edificio'
            'usuarioOrigen' => function ($query) {
                $query->with([
                    'resguardante' => function($q) {
                        $q->with('departamento.area', 'oficina.edificio');
                    }
                ]);
            },
            
            // --- Cargar datos de DESTINO ---
            // 'usuarioDestino.resguardante.departamento.area'
            // 'usuarioDestino.resguardante.oficina.edificio'
            'usuarioDestino' => function ($query) {
                $query->with([
                    'resguardante' => function($q) {
                        $q->with('departamento.area', 'oficina.edificio');
                    }
                ]);
            }
        ]);

        // 'traspaso_observaciones' (Motivo) ya viene en $traspaso.
        return response()->json($traspaso);
    }

    /**
     * Actualizar Estado de Solicitud
     *
     * Permite Aprobar o Rechazar una solicitud. Emite un evento WebSocket al cambiar el estado.
     *
     * @OA\Put(
     * path="/traspasos/{id}",
     * tags={"Traspasos"},
     * summary="Aprobar o Rechazar solicitud",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"estado"},
     * @OA\Property(property="estado", type="string", enum={"Aprobado", "Rechazado"}, example="Aprobado")
     * )
     * ),
     * @OA\Response(response=200, description="Estado actualizado"),
     * @OA\Response(response=422, description="Error de validación (estado inválido)"),
     * @OA\Response(response=500, description="Error del servidor")
     * )
     */
    public function update(Request $request, Traspaso $traspaso)
    {
        // 1. Validar la entrada. Solo aceptamos 'Aprobado' o 'Rechazado'
        // (Uso "Rechazado" para que coincida con tu tabla)
        $validatedData = $request->validate([
            'estado' => [
                'required',
                'string',
                Rule::in(['Aprobado', 'Rechazado'])
            ]
        ]);

        try {
            // 2. Iniciar la transacción
            DB::beginTransaction();

            // 3. Actualizar el estado del traspaso
            $traspaso->traspaso_estado = $validatedData['estado'];
            $traspaso->save();

            // 4. Lógica para generar el PDF (Paso 5)
            if ($traspaso->traspaso_estado === 'Aprobado') {
                // --- TU LÓGICA DE PDF IRÍA AQUÍ ---
                // Por ejemplo:
                // $pdf = Pdf::loadView('reportes.traspaso', ['traspaso' => $traspaso]);
                // Storage::put("public/traspasos/traspaso-{$traspaso->id}.pdf", $pdf->output());
                //
                // También deberíamos actualizar el bien/inventario, pero eso
                // lo podemos ver después.
            }

            // 5. ¡DISPARA EL EVENTO WEBSOCKET DE ACTUALIZACIÓN!
            broadcast(new SolicitudTraspasoActualizada($traspaso));

            // 6. Confirma la transacción
            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al actualizar la solicitud.',
                'message' => $e->getMessage()
            ], 500);
        }

        // 7. Devuelve la solicitud actualizada
        return response()->json($traspaso, 200);
    }

    /**
     * Eliminar Solicitud
     *
     * @OA\Delete(
     * path="/traspasos/{id}",
     * tags={"Traspasos"},
     * summary="Eliminar solicitud (No implementado)",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Operación exitosa (si se implementa)")
     * )
     */
    public function destroy(Traspaso $traspaso)
    {
        //
    }
}