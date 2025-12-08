<?php

namespace App\Services\Lead;

use App\Contracts\WhatsAppSenderInterface;
use App\Enums\CampaignActionType;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStatus;
use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Enums\SourceStatus;
use App\Exceptions\Business\ConfigurationException;
use App\Models\Campaign\CampaignOption;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadMessage;
use Illuminate\Support\Facades\Log;

/**
 * Service for processing lead options according to campaign_options configuration
 */
class LeadOptionProcessorService
{
    public function __construct(
        private WhatsAppSenderInterface $whatsappSender
    ) {}

    /**
     * Process a lead option
     */
    public function processLeadOption(Lead $lead, CampaignOption $option): void
    {
        Log::info('Procesando opción de lead', [
            'lead_id' => $lead->id,
            'option_key' => $option->option_key,
            'action' => $option->action->value,
        ]);

        match ($option->action) {
            CampaignActionType::WHATSAPP => $this->processWhatsAppAction($lead, $option),
            CampaignActionType::CALL_AI => $this->processCallAIAction($lead, $option),
            CampaignActionType::WEBHOOK_CRM => $this->processWebhookAction($lead, $option),
            CampaignActionType::MANUAL_REVIEW => $this->processManualReviewAction($lead, $option),
            CampaignActionType::SKIP => null,
        };
    }

    protected function processWhatsAppAction(Lead $lead, CampaignOption $option): void
    {
        if (! $option->source_id) {
            $error = 'No hay source_id configurado para opción de WhatsApp';
            Log::warning($error, [
                'lead_id' => $lead->id,
                'option_id' => $option->id,
            ]);

            throw new ConfigurationException($error);
        }

        $source = $option->source;

        if (! $source) {
            $error = "Source no encontrado (ID: {$option->source_id})";
            Log::warning($error, [
                'lead_id' => $lead->id,
                'source_id' => $option->source_id,
            ]);

            throw new ConfigurationException($error);
        }

        if ($source->status !== SourceStatus::ACTIVE) {
            $error = "Fuente de WhatsApp no está activa (Estado: {$source->status->value})";
            Log::warning($error, [
                'lead_id' => $lead->id,
                'source_id' => $source->id,
                'source_status' => $source->status->value,
            ]);

            throw new ConfigurationException($error);
        }

        $message = $this->getMessage($option);

        try {
            $result = $this->whatsappSender->sendMessage(
                $source,
                $lead,
                $message,
                []
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
                'content' => $message,
                'metadata' => [
                    'source_id' => $source->id,
                    'option_key' => $option->option_key,
                    'result' => $result,
                ],
                'phone' => $lead->phone,
            ]);

            $lead->update([
                'status' => LeadStatus::IN_PROGRESS,
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
    }

    protected function processCallAIAction(Lead $lead, CampaignOption $option): void
    {
        Log::info('Procesando llamada con IA', [
            'lead_id' => $lead->id,
        ]);

        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'last_automation_run_at' => now(),
        ]);
    }

    protected function processWebhookAction(Lead $lead, CampaignOption $option): void
    {
        Log::info('Procesando webhook a CRM', [
            'lead_id' => $lead->id,
        ]);

        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'last_automation_run_at' => now(),
        ]);
    }

    protected function processManualReviewAction(Lead $lead, CampaignOption $option): void
    {
        Log::info('Marcando para revisión manual', [
            'lead_id' => $lead->id,
        ]);

        $lead->update([
            'status' => LeadStatus::PENDING,
            'last_automation_run_at' => now(),
        ]);
    }

    protected function getMessage(CampaignOption $option): string
    {
        if (!empty($option->message)) {
            $message = trim($option->message);
            if (!empty($message)) {
                return $message;
            }
        }

        if ($option->template_id && $option->template && !empty($option->template->content)) {
            $message = trim($option->template->content);
            if (!empty($message)) {
                return $message;
            }
        }

        return 'Gracias por tu interés. Un asesor se contactará contigo pronto.';
    }
}
