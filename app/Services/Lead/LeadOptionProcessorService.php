<?php

namespace App\Services\Lead;

use App\Enums\CampaignActionType;
use App\Enums\LeadStatus;
use App\Enums\SourceStatus;
use App\Models\CampaignOption;
use App\Models\Lead;
use App\Services\WhatsAppSenderService;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para procesar opciones de leads según la configuración de campaign_options
 */
class LeadOptionProcessorService
{
    public function __construct(
        private WhatsAppSenderService $whatsappSender
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
        if (!$option->source_id) {
            Log::warning('No hay source_id configurado para opción de WhatsApp', [
                'lead_id' => $lead->id,
                'option_id' => $option->id,
            ]);
            return;
        }

        // Obtener la fuente de WhatsApp
        $source = $option->source;
        
        if (!$source) {
            Log::warning('Source no encontrado', [
                'lead_id' => $lead->id,
                'source_id' => $option->source_id,
            ]);
            return;
        }

        // Verificar que la fuente esté activa
        if ($source->status !== SourceStatus::ACTIVE) {
            Log::warning('Fuente de WhatsApp no está activa', [
                'lead_id' => $lead->id,
                'source_id' => $source->id,
                'source_status' => $source->status->value,
            ]);
            return;
        }

        // Obtener el mensaje
        $message = $this->getMessage($option);

        if (!$message) {
            Log::warning('No hay mensaje configurado', [
                'lead_id' => $lead->id,
                'option_id' => $option->id,
            ]);
            return;
        }

        // Enviar mensaje
        try {
            $result = $this->whatsappSender->sendMessage(
                $source,
                $lead,
                $message
            );

            Log::info('Mensaje WhatsApp enviado exitosamente', [
                'lead_id' => $lead->id,
                'source_id' => $source->id,
                'result' => $result,
            ]);

            // Actualizar estado del lead
            $lead->update([
                'status' => LeadStatus::IN_PROGRESS,
                'automation_status' => 'completed',
                'last_automation_run_at' => now(),
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
        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'automation_status' => 'completed',
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
        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'automation_status' => 'completed',
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

        $lead->update([
            'status' => LeadStatus::PENDING,
            'automation_status' => 'manual_review',
            'last_automation_run_at' => now(),
        ]);
    }

    /**
     * Obtener el mensaje a enviar
     */
    protected function getMessage(CampaignOption $option): ?string
    {
        // 1. Prioridad: mensaje directo en la opción
        if ($option->message) {
            return $option->message;
        }

        // 2. Si hay template_id, usar el template
        if ($option->template_id && $option->template) {
            return $option->template->content;
        }

        // 3. Mensaje genérico por defecto
        return 'Gracias por tu interés. Un asesor se contactará contigo pronto.';
    }
}

