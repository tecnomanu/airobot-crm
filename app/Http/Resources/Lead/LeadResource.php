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
            'email' => $this->email,
            'city' => $this->city,
            'country' => $this->country,
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

            // === STAGE: Single source of truth ===
            'stage' => $this->stage?->value,
            'stage_label' => $this->stage?->label(),
            'stage_color' => $this->stage?->color(),
            'can_start_automation' => $this->stage?->canStartAutomation() ?? true,
            'can_close' => $this->stage?->canClose() ?? true,
            'is_closed' => $this->stage?->isTerminal() ?? false,

            // === CLOSE FIELDS ===
            'closed_at' => $this->closed_at?->toIso8601String(),
            'close_reason' => $this->close_reason?->value,
            'close_reason_label' => $this->close_reason?->label(),
            'close_reason_color' => $this->close_reason?->color(),
            'close_notes' => $this->close_notes,

            // Legacy status (kept for backwards compatibility)
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'source' => $this->source,
            'source_label' => $this->getSourceLabel(),
            'sent_at' => $this->sent_at?->toIso8601String(),

            // Intention tracking
            'intention' => $this->intention,
            'intention_status' => $this->intention_status?->value,
            'intention_status_label' => $this->intention_status?->label(),
            'intention_origin' => $this->intention_origin?->value,
            'intention_decided_at' => $this->intention_decided_at?->toIso8601String(),

            // Messages
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

            // Calls
            'calls' => $this->whenLoaded('calls', function () {
                return $this->calls->map(function ($call) {
                    return [
                        'id' => $call->id,
                        'call_date' => $call->call_date?->toIso8601String(),
                        'duration_seconds' => $call->duration_seconds,
                        'status' => $call->status,
                        'summary' => $call->summary ?? null,
                        'notes' => $call->notes,
                        'recording_url' => $call->recording_url,
                        'created_at' => $call->created_at->toIso8601String(),
                    ];
                });
            }),

            // Dispatch attempts
            'dispatch_attempts' => $this->whenLoaded('dispatchAttempts', function () {
                return LeadDispatchAttemptResource::collection($this->dispatchAttempts);
            }),

            // Notes and tags
            'notes' => $this->notes,
            'tags' => $this->tags,
            'manual_classification' => $this->manual_classification,
            'decision_notes' => $this->decision_notes,
            'ai_agent_active' => $this->ai_agent_active ?? false,

            // Legacy webhook tracking
            'webhook_sent' => $this->webhook_sent,
            'webhook_result' => $this->webhook_result,
            'intention_webhook_sent' => $this->intention_webhook_sent,
            'intention_webhook_sent_at' => $this->intention_webhook_sent_at?->toIso8601String(),
            'intention_webhook_status' => $this->intention_webhook_status,
            'intention_webhook_response' => $this->intention_webhook_response,

            // Automation
            'automation_status' => $this->automation_status?->value,
            'automation_status_label' => $this->automation_status?->label(),
            'automation_status_color' => $this->automation_status?->color(),
            'automation_attempts' => $this->automation_attempts,
            'automation_error' => $this->automation_error,
            'last_automation_run_at' => $this->last_automation_run_at?->toIso8601String(),
            'next_action_at' => $this->next_action_at?->toIso8601String(),
            'next_action_label' => $this->getNextActionLabel(),
            'can_retry_automation' => $this->canRetryAutomation(),

            // Contact source info
            'contact_source_name' => $this->getContactSourceName(),
            'contact_source_phone' => $this->getContactSourcePhone(),

            // Assignment
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

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function getOptionLabel(): ?string
    {
        if (!$this->option_selected) {
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

    private function getSourceLabel(): string
    {
        if (!$this->source) {
            return 'Desconocido';
        }

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

        return $sourceMap[$this->source] ?? ucwords(str_replace(['_', '-'], ' ', $this->source));
    }

    private function getLastInboundMessage(): ?string
    {
        if ($this->relationLoaded('messages')) {
            $lastInbound = $this->messages
                ->where('direction', 'inbound')
                ->sortByDesc('created_at')
                ->first();

            return $lastInbound?->content;
        }

        $lastInbound = $this->messages()
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastInbound?->content;
    }

    private function canRetryAutomation(): bool
    {
        // Can retry if has error and stage allows automation
        return ($this->automation_error !== null
            || in_array($this->automation_status?->value, ['failed', 'pending']))
            && $this->stage?->canStartAutomation();
    }

    private function getContactSourceName(): ?string
    {
        if ($this->campaign) {
            $config = $this->campaign->configuration ?? [];
            $sourceId = $config['source_id'] ?? null;

            if (!$sourceId && $this->option_selected && isset($config['mapping'][$this->option_selected])) {
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

    private function getContactSourcePhone(): ?string
    {
        if ($this->campaign) {
            $config = $this->campaign->configuration ?? [];
            $sourceId = $config['source_id'] ?? null;

            if (!$sourceId && $this->option_selected && isset($config['mapping'][$this->option_selected])) {
                $sourceId = $config['mapping'][$this->option_selected]['source_id'] ?? null;
            }

            if ($sourceId) {
                $source = \App\Models\Integration\Source::find($sourceId);
                if ($source) {
                    $sourceConfig = $source->config ?? [];
                    return $sourceConfig['phone_number'] ?? $sourceConfig['phone'] ?? null;
                }
            }
        }

        return null;
    }

    private function getNextActionLabel(): ?string
    {
        if (!$this->next_action_at) {
            return null;
        }

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
