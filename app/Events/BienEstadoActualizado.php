<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BienEstadoActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $bienId;
    public $nuevoEstado;
    public $oficinaId;
    /**
     * Create a new event instance.
     */
    public function __construct($bienId, $nuevoEstado, $oficinaId)
    {
        $this->bienId = $bienId;
        $this->nuevoEstado = $nuevoEstado;
        $this->oficinaId = $oficinaId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('oficina.' . $this->oficinaId),
        ];
    }
    public function broadcastAs()
    {
        return 'estado.cambiado';
    }
}
