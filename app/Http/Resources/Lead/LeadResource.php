<?php

namespace App\Http\Resources\Lead;

use App\Enums\LeadStage;
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
            'email' => $this->email,
            'city' => $this->city,
            'option_selected' => $this->option_selected,
            'option_selected_label' => $this->getOptionLabel(),
            'campaign_id' => $this->campaign_id,
            'campaign' => $this->whenLoaded('campaign', function () {
                return [
                    'id' => $this->campaign->id,
                    'name' => $this->campaign->name,
                    'client' => $this->campaign->relationLoaded('client') ? [
                        'id' => $this->campaign->client?->id,
                        'name' => $this->campaign->client?->name,
                    ] : null,
                ];
            }, [
                'id' => $this->campaign?->id,
                'name' => $this->campaign?->name,
            ]),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ];
            }),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'source' => $this->source,
            'source_label' => $this->getSourceLabel(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'intention' => $this->intention,
            'intention_status' => $this->intention_status?->value,
            'intention_status_label' => $this->intention_status?->label(),
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
            'calls' => $this->whenLoaded('calls', function () {
                return $this->calls->map(function ($call) {
                    return [
                        'id' => $call->id,
                        'call_date' => $call->call_date?->toIso8601String(),
                        'duration_seconds' => $call->duration_seconds,
                        'status' => $call->status,
                        'summary' => $call->summary,
                        'notes' => $call->notes,
                        'recording_url' => $call->recording_url,
                        'created_at' => $call->created_at->toIso8601String(),
                    ];
                });
            }),
            'notes' => $this->notes,
            'tags' => $this->tags,
            'manual_classification' => $this->manual_classification,
            'decision_notes' => $this->decision_notes,
            'ai_agent_active' => $this->ai_agent_active ?? false,
            'webhook_sent' => $this->webhook_sent,
            'webhook_result' => $this->webhook_result,
            'intention_webhook_sent' => $this->intention_webhook_sent,
            'intention_webhook_sent_at' => $this->intention_webhook_sent_at?->toIso8601String(),
            'intention_webhook_status' => $this->intention_webhook_status,
            'intention_webhook_response' => $this->intention_webhook_response,
            'automation_status' => $this->automation_status?->value,
            'automation_status_label' => $this->automation_status?->label(),
            'automation_status_color' => $this->automation_status?->color(),
            'automation_attempts' => $this->automation_attempts,
            'automation_error' => $this->automation_error,
            'last_automation_run_at' => $this->last_automation_run_at?->toIso8601String(),
            'next_action_at' => $this->next_action_at?->toIso8601String(),
            'next_action_label' => $this->getNextActionLabel(),
            'intention_decided_at' => $this->intention_decided_at?->toIso8601String(),
            'stage' => $this->getStageValue(),
            'stage_label' => $this->computeStageLabel(),
            'stage_color' => $this->computeStageColor(),
            'contact_source_name' => $this->getContactSourceName(),
            'contact_source_phone' => $this->getContactSourcePhone(),
            'can_retry_automation' => $this->canRetryAutomation(),
            'assigned_to' => $this->assigned_to,
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'assignment_error' => $this->assignment_error,
            'assignee' => $this->whenLoaded('assignee', function () {
                return [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                    'email' => $this->assignee->email,
                ];
            }),
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

    /**
     * Get the name of the source that contacted the lead (from campaign config or first outbound message)
     */
    private function getContactSourceName(): ?string
    {
        // Try to get from campaign configuration
        if ($this->campaign) {
            $config = $this->campaign->configuration ?? [];
            $sourceId = $config['source_id'] ?? null;

            // For dynamic campaigns, check the option mapping
            if (! $sourceId && $this->option_selected && isset($config['mapping'][$this->option_selected])) {
                $sourceId = $config['mapping'][$this->option_selected]['source_id'] ?? null;
            }

            if ($sourceId) {
                $source = \App\Models\Integration\Source::find($sourceId);
                if ($source) {
                    return $source->name;
                }
            }
        }

        return null;
    }

    /**
     * Get the phone number of the source that contacted the lead
     */
    private function getContactSourcePhone(): ?string
    {
        // Try to get from campaign configuration
        if ($this->campaign) {
            $config = $this->campaign->configuration ?? [];
            $sourceId = $config['source_id'] ?? null;

            // For dynamic campaigns, check the option mapping
            if (! $sourceId && $this->option_selected && isset($config['mapping'][$this->option_selected])) {
                $sourceId = $config['mapping'][$this->option_selected]['source_id'] ?? null;
            }

            if ($sourceId) {
                $source = \App\Models\Integration\Source::find($sourceId);
                if ($source) {
                    // Get phone from source config (stored in 'config' column, not 'configuration')
                    $sourceConfig = $source->config ?? [];

                    return $sourceConfig['phone_number'] ?? $sourceConfig['phone'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Compute the stage using LeadStage enum.
     *
     * The LeadStage enum provides the single source of truth for stage derivation.
     */
    private function computeStage(): LeadStage
    {
        return LeadStage::fromLead(
            $this->status,
            $this->automation_status,
            $this->intention_status,
            $this->intention
        );
    }

    /**
     * Get stage value as string for JSON output.
     */
    private function getStageValue(): string
    {
        return $this->computeStage()->value;
    }

    /**
     * Get readable label for computed stage.
     */
    private function computeStageLabel(): string
    {
        return $this->computeStage()->label();
    }

    /**
     * Get color for computed stage.
     */
    private function computeStageColor(): string
    {
        return $this->computeStage()->color();
    }

    /**
     * Get next action label based on next_action_at
     */
    private function getNextActionLabel(): ?string
    {
        if (! $this->next_action_at) {
            return null;
        }

        $now = now();
        $nextAction = $this->next_action_at;

        if ($nextAction->isPast()) {
            return 'Pendiente';
        }

        if ($nextAction->isToday()) {
            return 'Hoy ' . $nextAction->format('H:i');
        }

        if ($nextAction->isTomorrow()) {
            return 'Mañana ' . $nextAction->format('H:i');
        }

        return $nextAction->format('d/m H:i');
    }
}
