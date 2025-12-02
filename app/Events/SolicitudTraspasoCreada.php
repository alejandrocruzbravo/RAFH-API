<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
//use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;


class SolicitudTraspasoCreada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $traspaso;
    /**
     * Create a new event instance.
     */
    public function __construct($traspaso)
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
    /**
     * Define el nombre del evento que escuchará el frontend.
     */
    public function broadcastAs(): string
    {
        return 'solicitud.creada';
    }

    /**
     * Prepara los datos que se enviarán al frontend.
     */
    public function broadcastWith(): array
    {
        $payload = [
            'id' => $this->traspaso->id,
            'bien_nombre' => $this->traspaso->bien->bien_nombre,
            'emisor' => $this->traspaso->resguardanteOrigen->res_nombre,
            'receptor' => $this->traspaso->resguardanteDestino->res_nombre,
            'estado' => $this->traspaso->traspaso_estado,
            'fecha' => $this->traspaso->traspaso_fecha_solicitud->toFormattedDateString(),
        ];
        // Enviamos solo los datos que la notificación necesita
        Log::debug($payload);
        return $payload;
    }
}
