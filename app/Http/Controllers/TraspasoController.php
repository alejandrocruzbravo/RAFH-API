<?php

namespace App\Http\Controllers;

use App\Models\Traspaso;
use Illuminate\Http\Request;
use App\Events\SolicitudTraspasoCreada;

use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\DB;
use Throwable;                          
use Illuminate\Support\Facades\Log;
use App\Events\SolicitudTraspasoActualizada;
use Illuminate\Validation\Rule;
class TraspasoController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
/**
     * Almacena una nueva solicitud de traspaso y dispara el evento WebSocket.
     * (Paso 2: Preparar las peticiones POST)
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function destroy(Traspaso $traspaso)
    {
        //
    }
}
