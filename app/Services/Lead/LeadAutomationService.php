<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Contracts\WebhookSenderInterface;
use App\Contracts\WhatsAppSenderInterface;
use App\Enums\CampaignActionType;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Enums\SourceStatus;
use App\Exceptions\Business\ValidationException;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadMessage;
use Illuminate\Support\Facades\Log;

/**
 * Service for lead automation actions according to campaign configuration
 */
class LeadAutomationService
{
    public function __construct(
        private WhatsAppSenderInterface $whatsappSender,
        private WebhookSenderInterface $webhookSender,
        private LeadService $leadService,
        private LeadExportService $leadExportService,
    ) {}

    /**
     * Execute configured action for a lead option
     */
    public function executeActionForOption(Lead $lead, string $optionField): void
    {
        $campaign = $lead->campaign;

        if (! $campaign) {
            throw new ValidationException('Lead no tiene campaña asociada');
        }

        $actionValue = null;

        // Try to resolve action from CampaignOption relation or configuration
        if (preg_match('/option_(.*?)_action/', $optionField, $matches)) {
            $optionKey = $matches[1];
            
            // 1. Try relation
            $option = $campaign->getOption($optionKey);
            if ($option) {
                $actionValue = $option->action;
                // If action is enum, get backing value if needed, but DB likely stores string
                if ($actionValue instanceof \BackedEnum) {
                    $actionValue = $actionValue->value;
                }
            } 
            
            // 2. Fallback to config
            if (!$actionValue) {
                $actionValue = $campaign->getActionForOption($optionKey);
            }
        }

        // 3. Last resort: attribute access (legacy)
        if (!$actionValue) {
            $actionValue = $campaign->{$optionField};
        }

        if (! $actionValue) {
            Log::info('No hay acción configurada para esta opción', [
                'lead_id' => $lead->id,
                'option_field' => $optionField,
            ]);

            return;
        }

        try {
            $actionType = CampaignActionType::from($actionValue);
        } catch (\ValueError $e) {
            Log::warning('Tipo de acción inválido', [
                'lead_id' => $lead->id,
                'action_value' => $actionValue,
            ]);

            return;
        }

        Log::info('Ejecutando acción de automatización', [
            'lead_id' => $lead->id,
            'action_type' => $actionType->value,
            'option_field' => $optionField,
        ]);

        match ($actionType) {
            CampaignActionType::WHATSAPP => $this->executeWhatsAppAction($lead, $optionField),
            CampaignActionType::WEBHOOK_CRM => $this->executeWebhookAction($lead, $optionField),
            CampaignActionType::CALL_AI => $this->executeCallAIAction($lead),
            CampaignActionType::MANUAL_REVIEW => $this->executeManualReviewAction($lead),
            CampaignActionType::SKIP => null,
        };
    }

    protected function executeWhatsAppAction(Lead $lead, string $optionField): void
    {
        $campaign = $lead->campaign;
        $source = null;

        // 1. Try to get source from Option configuration
        // Extract key from optionField (e.g. 'option_2_action' -> '2')
        if (preg_match('/option_(.*?)_action/', $optionField, $matches)) {
            $optionKey = $matches[1];

            // Try getting from relation first (preferred)
            $option = $campaign->getOption($optionKey);
            if ($option && $option->source_id) {
                $source = $option->source;
            } elseif ($option && $option->config && !empty($option->config['source_id'])) {
                // Fallback if source_id is in config json of the option
                $source = \App\Models\Integration\Source::find($option->config['source_id']);
            } else {
                // Fallback to JSON config on campaign
                $optionConfig = $campaign->getConfigForOption($optionKey);
                if (!empty($optionConfig['source_id'])) {
                    $source = \App\Models\Integration\Source::find($optionConfig['source_id']);
                }
            }
        }

        // 2. If not in option, try campaign resolved source (campaign -> client default)
        if (!$source) {
            $source = $campaign->getResolvedWhatsappSource();
        }

        if ($source) {
            if ($source->status !== SourceStatus::ACTIVE) {
                Log::warning('Fuente de WhatsApp no está activa', [
                    'lead_id' => $lead->id,
                    'source_id' => $source->id,
                    'source_status' => $source->status->value,
                ]);
                throw new ValidationException(
                    "La fuente de WhatsApp '{$source->name}' no está activa"
                );
            }

            $messageBody = $this->getMessageForOption($campaign, $optionField);

            if (! $messageBody) {
                Log::warning('No hay mensaje configurado para esta opción', [
                    'lead_id' => $lead->id,
                    'option_field' => $optionField,
                ]);

                return;
            }

            try {
                $result = $this->whatsappSender->sendMessage(
                    $source,
                    $lead,
                    $messageBody
                );

                Log::info('Mensaje WhatsApp enviado exitosamente', [
                    'lead_id' => $lead->id,
                    'source_id' => $source->id,
                    'result' => $result,
                ]);

                // Create LeadMessage (replaces LeadInteraction)
                LeadMessage::create([
                    'lead_id' => $lead->id,
                    'campaign_id' => $lead->campaign_id,
                    'channel' => MessageChannel::WHATSAPP,
                    'direction' => MessageDirection::OUTBOUND,
                    'status' => MessageStatus::SENT,
                    'content' => $messageBody,
                    'metadata' => [
                        'source_id' => $source->id,
                        'option_field' => $optionField,
                        'result' => $result,
                    ],
                    'phone' => $lead->phone,
                ]);

                $lead->update([
                    'intention_status' => LeadIntentionStatus::PENDING,
                    'intention_origin' => LeadIntentionOrigin::WHATSAPP,
                ]);

                Log::info('Intent registrado como PENDING para lead', [
                    'lead_id' => $lead->id,
                    'origin' => 'whatsapp',
                ]);
            } catch (\Exception $e) {
                Log::error('Error enviando mensaje WhatsApp', [
                    'lead_id' => $lead->id,
                    'source_id' => $source->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        } else {
            Log::warning('Campaña no tiene fuente de WhatsApp configurada', [
                'lead_id' => $lead->id,
                'campaign_id' => $campaign->id,
                'client_id' => $campaign->client_id,
                'option_field' => $optionField,
                'use_client_defaults' => $campaign->use_client_whatsapp_defaults,
            ]);

            throw new ValidationException(
                'Campaña no tiene fuente de WhatsApp configurada. ' .
                    'Configure una Source de tipo WhatsApp para esta campaña, opción, o configure un default en el Cliente.'
            );
        }
    }

    protected function executeWebhookAction(Lead $lead, string $optionField = ''): void
    {
        // Resolve source to check status
        $campaign = $lead->campaign;
        $source = null;
        
        // Similar logic to resolve source (could be refactored to helper)
        if ($optionField && preg_match('/option_(.*?)_action/', $optionField, $matches)) {
            $optionKey = $matches[1];
             $option = $campaign->getOption($optionKey);
            if ($option && $option->source_id) {
                 $source = $option->source;
            } elseif ($option && $option->config && !empty($option->config['source_id'])) {
                 $source = \App\Models\Integration\Source::find($option->config['source_id']);
            } else {
                $optionConfig = $campaign->getConfigForOption($optionKey);
                if (!empty($optionConfig['source_id'])) {
                    $source = \App\Models\Integration\Source::find($optionConfig['source_id']);
                }
            }
        }
        
        if ($source && $source->status !== SourceStatus::ACTIVE) {
             throw new ValidationException("La fuente de Webhook '{$source->name}' no está activa");
        }

        Log::info('Ejecutando acción de exportación', [
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'intention' => $lead->intention,
            'intention_status' => $lead->intention_status?->value,
        ]);

        $exported = $this->leadExportService->exportLead($lead);

        if (! $exported) {
            Log::info('Lead no se exportó según reglas de campaña', [
                'lead_id' => $lead->id,
                'campaign_type' => $lead->campaign->campaign_type?->value,
                'export_rule' => $lead->campaign->export_rule?->value,
            ]);
        }
    }

    protected function executeCallAIAction(Lead $lead): void
    {
        Log::info('Ejecutando acción de llamada con IA', [
            'lead_id' => $lead->id,
        ]);

        throw new \Exception('Acción de llamada con IA no implementada aún');
    }

    protected function executeManualReviewAction(Lead $lead): void
    {
        Log::info('Lead marcado para revisión manual', [
            'lead_id' => $lead->id,
        ]);
    }

    protected function getMessageForOption($campaign, string $optionField): ?string
    {
        // 1. Check CampaignOption relation
        if (preg_match('/option_(.*?)_action/', $optionField, $matches)) {
            $optionKey = $matches[1];
            $option = $campaign->getOption($optionKey);
            
            if ($option && !empty($option->message)) {
                return $option->message;
            }
            
            // Logica legacy/array fallback
            $config = $campaign->getConfigForOption($optionKey);
            if (!empty($config['message'])) {
                return $config['message'];
            }
        }

        $messageField = match ($optionField) {
            'option_2_action' => 'option_2_message',
            'option_i_action' => 'option_i_message',
            default => null,
        };

        if ($messageField && $campaign->{$messageField}) {
            return $campaign->{$messageField};
        }

        return 'Gracias por tu interés. Un asesor se contactará contigo pronto.';
    }
}
