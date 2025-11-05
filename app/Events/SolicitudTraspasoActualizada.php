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
        // El frontend solo necesita saber el ID y el nuevo estado
        return [
            'id' => $this->traspaso->id,
            'estado' => $this->traspaso->traspaso_estado,
        ];
    }
}
