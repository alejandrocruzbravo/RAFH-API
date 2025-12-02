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