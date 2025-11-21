<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un lead es creado o actualizado
 * Se transmite en tiempo real al frontend vía WebSockets
 */
class LeadUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public string $action = 'updated' // 'created' | 'updated' | 'deleted'
    ) {}

    /**
     * Canal público donde se transmite el evento
     */
    public function broadcastOn(): Channel
    {
        return new Channel('leads');
    }

    /**
     * Nombre del evento en el frontend
     */
    public function broadcastAs(): string
    {
        return 'lead.updated';
    }

    /**
     * Datos que se envían al frontend
     */
    public function broadcastWith(): array
    {
        return [
            'lead' => [
                'id' => $this->lead->id,
                'phone' => $this->lead->phone,
                'name' => $this->lead->name,
                'city' => $this->lead->city,
                'status' => $this->lead->status,
                'option_selected' => $this->lead->option_selected,
                'campaign_id' => $this->lead->campaign_id,
                'campaign' => $this->lead->campaign ? [
                    'id' => $this->lead->campaign->id,
                    'name' => $this->lead->campaign->name,
                    'slug' => $this->lead->campaign->slug,
                ] : null,
                'automation_status' => $this->lead->automation_status,
                'notes' => $this->lead->notes,
                'created_at' => $this->lead->created_at?->toISOString(),
                'updated_at' => $this->lead->updated_at?->toISOString(),
            ],
            'action' => $this->action,
            'timestamp' => now()->toISOString(),
        ];
    }
}
