<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'client_id' => $this->client_id,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'slug' => $this->slug,
            'strategy_type' => $this->strategy_type?->value ?? 'dynamic',
            'strategy_type_label' => $this->strategy_type?->label() ?? 'Dinámica (Opciones)',
            'is_direct' => $this->isDirect(),
            'is_dynamic' => $this->isDynamic(),
            'auto_process_enabled' => $this->auto_process_enabled,

            // Agente de llamadas
            'call_agent' => $this->whenLoaded('callAgent', function () {
                if (! $this->callAgent) {
                    return null;
                }

                return [
                    'id' => $this->callAgent->id,
                    'name' => $this->callAgent->name,
                    'provider' => $this->callAgent->provider->value,
                    'provider_label' => $this->callAgent->provider->label(),
                    'config' => $this->callAgent->config,
                    'enabled' => $this->callAgent->enabled,
                ];
            }),

            // Agente de WhatsApp
            'whatsapp_agent' => $this->whenLoaded('whatsappAgent', function () {
                if (! $this->whatsappAgent) {
                    return null;
                }

                return [
                    'id' => $this->whatsappAgent->id,
                    'name' => $this->whatsappAgent->name,
                    'source_id' => $this->whatsappAgent->source_id,
                    'config' => $this->whatsappAgent->config,
                    'enabled' => $this->whatsappAgent->enabled,
                    'source' => $this->whatsappAgent->source ? [
                        'id' => $this->whatsappAgent->source->id,
                        'name' => $this->whatsappAgent->source->name,
                        'type' => $this->whatsappAgent->source->type->value,
                        'type_label' => $this->whatsappAgent->source->type->label(),
                        'status' => $this->whatsappAgent->source->status->value,
                    ] : null,
                ];
            }),

            // Opciones de la campaña
            'options' => $this->whenLoaded('options', function () {
                return $this->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'option_key' => $option->option_key,
                        'action' => $option->action->value,
                        'action_label' => $option->action->label(),
                        'source_id' => $option->source_id,
                        'template_id' => $option->template_id,
                        'message' => $option->message,
                        'delay' => $option->delay,
                        'enabled' => $option->enabled,
                        'source' => $option->source ? [
                            'id' => $option->source->id,
                            'name' => $option->source->name,
                            'type' => $option->source->type->value,
                            'type_label' => $option->source->type->label(),
                        ] : null,
                        'template' => $option->template ? [
                            'id' => $option->template->id,
                            'name' => $option->template->name,
                            'code' => $option->template->code,
                        ] : null,
                    ];
                });
            }),

            // Intention Actions (nueva estructura)
            'intention_actions' => $this->whenLoaded('intentionActions', function () {
                return CampaignIntentionActionResource::collection($this->intentionActions);
            }),

            // Compatibilidad con frontend legacy (mapeo de intentionActions a campos antiguos)
            'intention_interested_webhook_id' => $this->getInterestedAction()?->webhook_id,
            'intention_not_interested_webhook_id' => $this->getNotInterestedAction()?->webhook_id,
            'send_intention_interested_webhook' => $this->shouldSendInterestedWebhook(),
            'send_intention_not_interested_webhook' => $this->shouldSendNotInterestedWebhook(),
            'google_integration_id' => $this->getInterestedAction()?->google_integration_id,
            'google_spreadsheet_id' => $this->getInterestedAction()?->google_spreadsheet_id ?? '',
            'google_sheet_name' => $this->getInterestedAction()?->google_sheet_name ?? '',
            'intention_not_interested_google_spreadsheet_id' => $this->getNotInterestedAction()?->google_spreadsheet_id ?? '',
            'intention_not_interested_google_sheet_name' => $this->getNotInterestedAction()?->google_sheet_name ?? '',

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relaciones opcionales
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'company' => $this->client->company,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),

            // Templates de WhatsApp
            'whatsapp_templates' => $this->whenLoaded('whatsappTemplates', function () {
                return $this->whatsappTemplates->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'code' => $template->code,
                        'name' => $template->name,
                        'body' => $template->body,
                        'attachments' => $template->attachments,
                        'is_default' => $template->is_default,
                    ];
                });
            }),
        ];
    }
}
