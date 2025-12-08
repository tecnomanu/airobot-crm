<?php

namespace App\Events;

use App\Models\CallHistory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando se completa una nueva llamada
 * Se transmite en tiempo real al frontend vía WebSockets
 */
class CallHistoryCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CallHistory $call
    ) {}

    /**
     * Canal público donde se transmite el evento
     */
    public function broadcastOn(): Channel
    {
        return new Channel('call-history');
    }

    /**
     * Nombre del evento en el frontend
     */
    public function broadcastAs(): string
    {
        return 'call.created';
    }

    /**
     * Datos que se envían al frontend
     */
    public function broadcastWith(): array
    {
        return [
            'call' => [
                'id' => $this->call->id,
                'phone' => $this->call->phone,
                'duration' => $this->call->duration,
                'status' => $this->call->status,
                'cost' => $this->call->cost,
                'campaign' => $this->call->campaign ? [
                    'id' => $this->call->campaign->id,
                    'name' => $this->call->campaign->name,
                ] : null,
                'agent' => $this->call->agent ? [
                    'id' => $this->call->agent->id,
                    'name' => $this->call->agent->name,
                ] : null,
                'created_at' => $this->call->created_at?->toISOString(),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}

