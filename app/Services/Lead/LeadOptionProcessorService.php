<?php

namespace App\Services\Lead;

use App\Enums\CampaignActionType;
use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Enums\LeadAutomationStatus;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStatus;
use App\Enums\SourceStatus;
use App\Exceptions\Business\ConfigurationException;
use App\Contracts\WhatsAppSenderInterface;
use App\Models\CampaignOption;
use App\Models\Lead;
use App\Models\LeadInteraction;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para procesar opciones de leads según la configuración de campaign_options
 */
class LeadOptionProcessorService
{
    public function __construct(
        private WhatsAppSenderInterface $whatsappSender
    ) {}

    /**
     * Procesar una opción de lead
     */
    public function processLeadOption(Lead $lead, CampaignOption $option): void
    {
        Log::info('Procesando opción de lead', [
            'lead_id' => $lead->id,
            'option_key' => $option->option_key,
            'action' => $option->action->value,
        ]);

        // Ejecutar según tipo de acción
        match ($option->action) {
            CampaignActionType::WHATSAPP => $this->processWhatsAppAction($lead, $option),
            CampaignActionType::CALL_AI => $this->processCallAIAction($lead, $option),
            CampaignActionType::WEBHOOK_CRM => $this->processWebhookAction($lead, $option),
            CampaignActionType::MANUAL_REVIEW => $this->processManualReviewAction($lead, $option),
            CampaignActionType::SKIP => null, // No hacer nada
        };
    }

    /**
     * Procesar acción de WhatsApp
     */
    protected function processWhatsAppAction(Lead $lead, CampaignOption $option): void
    {
        // Verificar que haya un source_id configurado
        if (! $option->source_id) {
            $error = 'No hay source_id configurado para opción de WhatsApp';
            Log::warning($error, [
                'lead_id' => $lead->id,
                'option_id' => $option->id,
            ]);

            throw new ConfigurationException($error);
        }

        // Obtener la fuente de WhatsApp
        $source = $option->source;

        if (! $source) {
            $error = "Source no encontrado (ID: {$option->source_id})";
            Log::warning($error, [
                'lead_id' => $lead->id,
                'source_id' => $option->source_id,
            ]);

            throw new ConfigurationException($error);
        }

        // Verificar que la fuente esté activa
        if ($source->status !== SourceStatus::ACTIVE) {
            $error = "Fuente de WhatsApp no está activa (Estado: {$source->status->value})";
            Log::warning($error, [
                'lead_id' => $lead->id,
                'source_id' => $source->id,
                'source_status' => $source->status->value,
            ]);

            throw new ConfigurationException($error);
        }

        // Obtener el mensaje (siempre retorna un mensaje válido)
        $message = $this->getMessage($option);

        // Enviar mensaje
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

            // Registrar interacción outbound
            LeadInteraction::create([
                'lead_id' => $lead->id,
                'campaign_id' => $lead->campaign_id,
                'channel' => InteractionChannel::WHATSAPP,
                'direction' => InteractionDirection::OUTBOUND,
                'content' => $message,
                'payload' => [
                    'source_id' => $source->id,
                    'option_key' => $option->option_key,
                    'result' => $result,
                ],
                'phone' => $lead->phone,
            ]);

            // Actualizar estado del lead: IN_PROGRESS porque se procesó con WhatsApp
            // El automation_status se actualiza en autoProcessLeadIfEnabled
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

    /**
     * Procesar acción de llamada con IA
     */
    protected function processCallAIAction(Lead $lead, CampaignOption $option): void
    {
        Log::info('Procesando llamada con IA', [
            'lead_id' => $lead->id,
        ]);

        // TODO: Implementar lógica de llamada con IA
        // Por ahora solo actualizamos el estado
        // Nota: automation_status se actualiza en autoProcessLeadIfEnabled
        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'last_automation_run_at' => now(),
        ]);
    }

    /**
     * Procesar acción de webhook a CRM
     */
    protected function processWebhookAction(Lead $lead, CampaignOption $option): void
    {
        Log::info('Procesando webhook a CRM', [
            'lead_id' => $lead->id,
        ]);

        // TODO: Implementar lógica de webhook
        // Por ahora solo actualizamos el estado
        // Nota: automation_status se actualiza en autoProcessLeadIfEnabled
        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'last_automation_run_at' => now(),
        ]);
    }

    /**
     * Procesar acción de revisión manual
     */
    protected function processManualReviewAction(Lead $lead, CampaignOption $option): void
    {
        Log::info('Marcando para revisión manual', [
            'lead_id' => $lead->id,
        ]);

        // Para revisión manual, marcamos como SKIPPED ya que requiere intervención humana
        // Nota: automation_status se actualiza en autoProcessLeadIfEnabled
        $lead->update([
            'status' => LeadStatus::PENDING,
            'last_automation_run_at' => now(),
        ]);
    }

    /**
     * Obtener el mensaje a enviar
     * Siempre retorna un mensaje válido (nunca null o vacío)
     */
    protected function getMessage(CampaignOption $option): string
    {
        // 1. Prioridad: mensaje directo en la opción
        if (!empty($option->message)) {
            $message = trim($option->message);
            if (!empty($message)) {
                return $message;
            }
        }

        // 2. Si hay template_id, usar el template
        if ($option->template_id && $option->template && !empty($option->template->content)) {
            $message = trim($option->template->content);
            if (!empty($message)) {
                return $message;
            }
        }

        // 3. Mensaje genérico por defecto (siempre retorna algo)
        return 'Gracias por tu interés. Un asesor se contactará contigo pronto.';
    }
}
