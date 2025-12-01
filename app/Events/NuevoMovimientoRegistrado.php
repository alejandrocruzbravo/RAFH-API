<?php

namespace App\Events;

use App\Models\MovimientoBien;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // <--- IMPORTANTE
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Debe implementar ShouldBroadcast para salir por WebSocket
class NuevoMovimientoRegistrado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $movimiento;

    // Recibimos el movimiento recién creado
    public function __construct(MovimientoBien $movimiento)
    {
        $this->movimiento = $movimiento;
    }

    // Definimos el canal. Puede ser público o privado.
    // Usaremos uno público 'movimientos' para probar rápido.
    public function broadcastOn()
    {
        return new Channel('movimientos');
    }
    
    // Nombre del evento que escuchará Vue
    public function broadcastAs()
    {
        return 'nuevo.movimiento';
    }
}