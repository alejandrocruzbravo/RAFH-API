<?php

namespace App\Http\Controllers;

use App\Models\Traspaso;
use App\Models\Resguardo;
use App\Models\Resguardante;
//use App\Controllers\ResguardanteController;
use Illuminate\Http\Request;
use App\Events\SolicitudTraspasoCreada;
use App\Events\SolicitudTraspasoActualizada;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Log;
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
     * @OA\Get(
     * path="/traspasos",
     * summary="Listar solicitudes de traspaso",
     * description="Obtiene el historial de solicitudes de traspaso (pendientes, aprobadas, rechazadas) con filtros.",
     * tags={"Traspasos"},
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Buscar por descripción del bien o nombre del solicitante",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="estado",
     * in="query",
     * description="Filtrar por estado (Pendiente, Aprobada, Rechazada)",
     * required=false,
     * @OA\Schema(type="string", enum={"Pendiente", "Aprobada", "Rechazada"})
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
     * @OA\Property(property="data", type="array", @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=55),
     * @OA\Property(property="traspaso_estado", type="string", example="Pendiente"),
     * @OA\Property(property="traspaso_fecha_solicitud", type="string", format="date-time"),
     * @OA\Property(property="bien", type="object", @OA\Property(property="id", type="integer"), @OA\Property(property="bien_descripcion", type="string")),
     * @OA\Property(property="resguardanteOrigen", type="object", @OA\Property(property="id", type="integer"), @OA\Property(property="res_nombre", type="string"))
     * )),
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="total", type="integer", example=20)
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        $query = Traspaso::with([
            // Para la columna 'Solicitante'
            'resguardanteOrigen:id,res_nombre', 
            // Para la columna 'Descripción' (ej. "Transferencia de Laptop")
            'bien:id,bien_descripcion' // Asumo que la tabla 'bienes' tiene 'nombre'
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
                    $subQuery->where('descripcion', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('resguardanteOrigen', function($subQuery) use ($searchTerm) {
                    $subQuery->where('res_nombre', 'like', "%{$searchTerm}%");
                });
            });
        }

        // Ordena por la más reciente primero
        $solicitudes = $query->latest('traspaso_fecha_solicitud')->paginate(10);

        return $solicitudes;
    }


    /**
     * @OA\Post(
     * path="/traspasos",
     * summary="Solicitar un nuevo traspaso",
     * description="Crea una solicitud para transferir un bien del usuario actual a otro resguardante.",
     * tags={"Traspasos"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"traspaso_id_bien", "traspaso_id_usuario_destino"},
     * @OA\Property(property="traspaso_id_bien", type="integer", example=101, description="ID del bien a transferir"),
     * @OA\Property(property="traspaso_id_usuario_destino", type="integer", example=15, description="ID del Resguardante destinatario"),
     * @OA\Property(property="traspaso_observaciones", type="string", example="El monitor se entrega en buen estado", nullable=true)
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Solicitud creada exitosamente",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=56),
     * @OA\Property(property="traspaso_estado", type="string", example="Pendiente")
     * )
     * ),
     * @OA\Response(response=403, description="Prohibido: Usuario sin perfil de resguardante"),
     * @OA\Response(
     * response=422,
     * description="Error de validación (ej. intentar transferirse a sí mismo)",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="No puedes traspasar un bien a ti mismo."))
     * ),
     * @OA\Response(response=500, description="Error del servidor al procesar la transacción")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // 1. Obtenemos el ID de Resguardante del usuario actual (el que solicita)
        // Esto es necesario para verificar que no se envíe el bien a sí mismo.
        if (!$user->resguardante) {
            return response()->json(['message' => 'Tu usuario no tiene un perfil de resguardante asociado.'], 403);
        }
        $miResguardanteId = $user->resguardante->id;

        // 2. Validación corregida
        $validatedData = $request->validate([
            'traspaso_id_bien' => 'required|integer|exists:bienes,id',
            
            // CORRECCIÓN AQUÍ: Validamos contra la tabla 'resguardantes'
            'traspaso_id_usuario_destino' => [
                'required',
                'integer',
                'exists:resguardantes,id', // Debe existir en tabla resguardantes
                function ($attribute, $value, $fail) use ($miResguardanteId) {
                    // Validamos manualmente que el destino no sea el mismo que el origen
                    if ($value == $miResguardanteId) {
                        $fail('No puedes traspasar un bien a ti mismo.');
                    }
                },
            ],
            'traspaso_observaciones' => 'nullable|string|max:1000',
        ]);

        $traspaso = null;

        try {
            DB::beginTransaction();

            // 3. Creamos el registro
            // Nota: Guardamos el ID del RESGUARDANTE en las columnas de ID
            $traspaso = Traspaso::create([
                'traspaso_id_bien' => $validatedData['traspaso_id_bien'],
                
                // Origen: Guardamos ID del Resguardante (NO del usuario)
                // (Asegúrate de que tu base de datos espere esto. Si espera User ID, avísame, 
                // pero para el flujo de bienes lo lógico es guardar IDs de Resguardantes)
                'traspaso_id_usuario_origen' => $miResguardanteId, 
                
                // Destino: Guardamos el ID del Resguardante que llegó del Front
                'traspaso_id_usuario_destino' => $validatedData['traspaso_id_usuario_destino'],
                
                'traspaso_observaciones' => $validatedData['traspaso_observaciones'] ?? null,
                'traspaso_fecha_solicitud' => now(),
                'traspaso_estado' => 'Pendiente',
            ]);

            // 4. Cargar relaciones para el evento (Opcional, ajusta según tus relaciones)
            // Nota: Si cambiaste a guardar IDs de resguardantes, asegúrate que las relaciones
            // en el modelo Traspaso apunten a Resguardante::class, no Usuario::class.
            // $traspaso->load('bien', 'resguardanteOrigen', 'resguardanteDestino'); 
            
            broadcast(new SolicitudTraspasoCreada($traspaso));

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear la solicitud de traspaso.',
                'message' => $e->getMessage()
            ], 500);
        }

        return response()->json($traspaso, 201);
    }

    /**
     * @OA\Get(
     * path="/traspasos/{id}",
     * summary="Ver detalles de un traspaso",
     * description="Muestra la información completa del traspaso, incluyendo datos jerárquicos de origen y destino (Depto -> Área -> Edificio).",
     * tags={"Traspasos"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Detalles encontrados",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="id", type="integer", example=55),
     * @OA\Property(property="bien", type="object", description="Datos del bien"),
     * @OA\Property(property="resguardanteOrigen", type="object", description="Datos completos del emisor con depto/area/edificio"),
     * @OA\Property(property="resguardanteDestino", type="object", description="Datos completos del receptor con depto/area/edificio")
     * )
     * ),
     * @OA\Response(response=404, description="Solicitud no encontrada")
     * )
     */
    public function show(Traspaso $traspaso)
    {
        // El método load() funciona sobre ESTE traspaso en específico ($traspaso).
        // Usamos "Dot Notation" (puntos) para ir profundizando en las relaciones.
        
        $traspaso->load([
            // 1. Datos del Bien
            'bien', 

            // 2. Origen: Resguardante -> Depto -> Área / Oficina -> Edificio
            'resguardanteOrigen.departamento.area',
            'resguardanteOrigen.oficina.edificio',

            // 3. Destino: Resguardante -> Depto -> Área / Oficina -> Edificio
            'resguardanteDestino.departamento.area',
            'resguardanteDestino.oficina.edificio',
        ]);

        return response()->json($traspaso);
    }
    
    /**
     * @OA\Put(
     * path="/traspasos/{id}",
     * summary="Aprobar o Rechazar traspaso",
     * description="Procesa la solicitud. Si se aprueba, cambia automáticamente el dueño del bien y actualiza los registros de resguardo.",
     * tags={"Traspasos"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"estado"},
     * @OA\Property(property="estado", type="string", enum={"Aprobada", "Rechazada"}, description="Nuevo estado de la solicitud")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Solicitud procesada correctamente",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Solicitud Aprobada correctamente."),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=400, description="Error: La solicitud ya fue procesada anteriormente"),
     * @OA\Response(response=500, description="Error crítico al procesar la transacción")
     * )
     */
    public function update(Request $request, Traspaso $traspaso)
    {
        // 1. Validar entrada (Solo Aprobada o Rechazada)
        $validatedData = $request->validate([
            'estado' => ['required', 'string', 'in:Aprobada,Rechazada'] 
        ]);

        if ($traspaso->traspaso_estado !== 'Pendiente') {
            return response()->json(['message' => 'Esta solicitud ya fue procesada anteriormente.'], 400);
        }

        try {
            DB::beginTransaction();

            $nuevoEstado = $validatedData['estado'];

            // --- LÓGICA SI ES APROBADA ---
            if ($nuevoEstado === 'Aprobada') {
                
                // A. Actualizar el Bien (Cambio de dueño)
                $bien = $traspaso->bien; 
                
                if (!$bien) throw new \Exception("El bien asociado no existe.");

                // Cambiamos al nuevo dueño en la tabla BIENES
                $bien->id_resguardante = $traspaso->traspaso_id_usuario_destino;
                $bien->bien_estado = 'Activo'; // Lo reactivamos por si estaba en tránsito
                $bien->save();

                // --- B. GESTIÓN DE LA TABLA RESGUARDOS ---
                
                // 1. ELIMINAR el resguardo del dueño anterior
                // Buscamos el registro que unía a este bien con el resguardante origen
                Resguardo::where('resguardo_id_bien', $bien->id)
                    ->where('resguardo_id_resguardante', $traspaso->traspaso_id_usuario_origen)
                    ->delete();

                // 2. CREAR el nuevo resguardo para el nuevo dueño
                // Necesitamos los datos del resguardante destino para saber su departamento
                $resguardanteDestino = Resguardante::with('departamento')->find($traspaso->traspaso_id_usuario_destino);

                if ($resguardanteDestino) {
                    Resguardo::create([
                        'resguardo_id_bien' => $bien->id,
                        'resguardo_id_resguardante' => $resguardanteDestino->id,
                        'resguardo_fecha_asignacion' => now(),
                        // Asignamos el departamento del nuevo dueño (si tiene)
                        'resguardo_id_dep' => $resguardanteDestino->departamento ? $resguardanteDestino->departamento->id : null, 
                    ]);
                }
            }

            // --- C. ACTUALIZAR ESTADO DEL TRASPASO ---
            // (Ya quitamos la fecha que daba error)
            $traspaso->traspaso_estado = $nuevoEstado;
            $traspaso->save();

            // 3. Disparar Evento WebSocket
            broadcast(new SolicitudTraspasoActualizada($traspaso));

            DB::commit();

            return response()->json([
                'message' => "Solicitud {$nuevoEstado} correctamente.",
                'data' => $traspaso
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al procesar la solicitud.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}