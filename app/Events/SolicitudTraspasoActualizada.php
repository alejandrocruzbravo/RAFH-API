<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Models\Traspaso;

class SolicitudTraspasoActualizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $traspaso;

    /**
     * Create a new event instance.
     */
    public function __construct(Traspaso $traspaso)
    {
        $this->traspaso = $traspaso;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('solicitudes'),
        ];
    }
    public function broadcastAs(): string
    {
        // Un nombre de evento diferente
        return 'solicitud.actualizada';
    }
    
    public function broadcastWith(): array
    {
        // Cargamos las relaciones necesarias:
        // 1. El Bien (para el nombre)
        // 2. El Resguardante Origen -> y su Usuario (para obtener el ID de usuario login)
        $this->traspaso->load(['bien', 'resguardanteOrigen.usuario']);

        // Obtenemos el ID del USUARIO (Login) que solicitó el traspaso
        // Navegamos: Traspaso -> ResguardanteOrigen -> Usuario -> ID
        $userId = $this->traspaso->resguardanteOrigen 
                  && $this->traspaso->resguardanteOrigen->usuario 
                  ? $this->traspaso->resguardanteOrigen->usuario->id 
                  : null;

        return [
            'id' => $this->traspaso->id,
            'estado' => $this->traspaso->traspaso_estado,
            'bien_nombre' => $this->traspaso->bien ? $this->traspaso->bien->bien_descripcion : 'un bien',
            
            // ESTA ES LA CLAVE: Enviamos el ID de la tabla 'users' (ej. 3)
            // para que coincida con lo que tienes en localStorage
            'user_id_destinatario' => $userId, 
        ];
    }
}
