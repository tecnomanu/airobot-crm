<?php

namespace App\Http\Resources\Lead;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'name' => $this->name,
            'city' => $this->city,
            'option_selected' => $this->option_selected,
            'option_selected_label' => $this->getOptionLabel(),
            'campaign_id' => $this->campaign_id,
            'campaign' => [
                'id' => $this->campaign?->id,
                'name' => $this->campaign?->name,
            ],
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'source' => $this->source,
            'source_label' => $this->getSourceLabel(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'intention' => $this->intention,
            'intention_status' => $this->intention_status?->value,
            'intention_origin' => $this->intention_origin?->value,
            'last_message' => $this->getLastInboundMessage(),
            'messages_count' => $this->whenCounted('messages'),
            'messages' => $this->whenLoaded('messages', function () {
                return $this->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'direction' => $message->direction->value,
                        'channel' => $message->channel->value,
                        'content' => $message->content,
                        'created_at' => $message->created_at->toIso8601String(),
                    ];
                });
            }),
            'notes' => $this->notes,
            'tags' => $this->tags,
            'webhook_sent' => $this->webhook_sent,
            'webhook_result' => $this->webhook_result,
            'intention_webhook_sent' => $this->intention_webhook_sent,
            'intention_webhook_sent_at' => $this->intention_webhook_sent_at?->toIso8601String(),
            'intention_webhook_status' => $this->intention_webhook_status,
            'intention_webhook_response' => $this->intention_webhook_response,
            'automation_status' => $this->automation_status?->value,
            'automation_status_label' => $this->automation_status?->label(),
            'automation_attempts' => $this->automation_attempts,
            'automation_error' => $this->automation_error,
            'last_automation_run_at' => $this->last_automation_run_at?->toIso8601String(),
            'can_retry_automation' => $this->canRetryAutomation(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Obtiene el label descriptivo para option_selected
     */
    private function getOptionLabel(): ?string
    {
        if (! $this->option_selected) {
            return null;
        }

        return match ($this->option_selected) {
            '1' => 'Opción 1',
            '2' => 'Opción 2',
            'i' => 'Interesado',
            't' => 'Transferir',
            default => 'Opción ' . $this->option_selected,
        };
    }

    /**
     * Obtiene el label descriptivo para source (mapeo de valores comunes)
     */
    private function getSourceLabel(): string
    {
        if (! $this->source) {
            return 'Desconocido';
        }

        // Mapeo de fuentes comunes a labels amigables
        $sourceMap = [
            'webhook_inicial' => 'Webhook Inicial',
            'webhook_event' => 'Webhook por Evento',
            'whatsapp' => 'WhatsApp',
            'agente_ia' => 'Agente IA',
            'manual' => 'Manual',
            'landing_page' => 'Landing Page',
            'formulario' => 'Formulario Web',
            'facebook' => 'Facebook',
            'google_ads' => 'Google Ads',
            'chat_web' => 'Chat Web',
        ];

        // Si existe en el mapeo, usar el label; sino, formatear el valor
        return $sourceMap[$this->source] ?? $this->formatSourceLabel($this->source);
    }

    /**
     * Formatea el source para que sea más legible cuando no está en el mapeo
     */
    private function formatSourceLabel(string $source): string
    {
        // Reemplazar guiones bajos y guiones por espacios
        $formatted = str_replace(['_', '-'], ' ', $source);

        // Capitalizar cada palabra
        return ucwords($formatted);
    }

    /**
     * Obtiene el último mensaje inbound (del usuario) para mostrar en la tabla
     */
    private function getLastInboundMessage(): ?string
    {
        // Si los mensajes ya están cargados (eager loading), usar eso
        if ($this->relationLoaded('messages')) {
            $lastInbound = $this->messages
                ->where('direction', 'inbound')
                ->sortByDesc('created_at')
                ->first();

            return $lastInbound?->content;
        }

        // Fallback: hacer query si no están cargados (no debería pasar)
        $lastInbound = $this->messages()
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastInbound?->content;
    }

    /**
     * Determina si se puede reintentar el auto-procesamiento
     */
    private function canRetryAutomation(): bool
    {
        // Se puede reintentar si:
        // 1. Tiene error de automation
        // 2. O está en status failed o pending
        // 3. Y tiene una opción seleccionada
        return ($this->automation_error !== null
            || in_array($this->automation_status?->value, ['failed', 'pending']))
            && $this->option_selected !== null;
    }
}
