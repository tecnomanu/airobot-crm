<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Enums\CampaignActionType;
use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\SourceStatus;
use App\Exceptions\Business\ValidationException;
use App\Models\Lead;
use App\Models\LeadInteraction;
use App\Contracts\WebhookSenderInterface;
use App\Contracts\WhatsAppSenderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para automatización de acciones sobre leads
 * según la configuración de la campaña
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
     * Ejecutar acción configurada para una opción del lead
     *
     * @param  Lead  $lead  Lead a procesar
     * @param  string  $optionField  Campo de la campaña con la acción (option_1_action, option_2_action, etc.)
     *
     * @throws ValidationException
     */
    public function executeActionForOption(Lead $lead, string $optionField): void
    {
        $campaign = $lead->campaign;

        if (! $campaign) {
            throw new ValidationException('Lead no tiene campaña asociada');
        }

        // Obtener el tipo de acción configurado
        $actionValue = $campaign->{$optionField};

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

        // Ejecutar según tipo de acción
        match ($actionType) {
            CampaignActionType::WHATSAPP => $this->executeWhatsAppAction($lead, $optionField),
            CampaignActionType::WEBHOOK_CRM => $this->executeWebhookAction($lead),
            CampaignActionType::CALL_AI => $this->executeCallAIAction($lead),
            CampaignActionType::MANUAL_REVIEW => $this->executeManualReviewAction($lead),
            CampaignActionType::SKIP => null, // No hacer nada
        };
    }

    /**
     * Ejecutar acción de envío de WhatsApp
     */
    protected function executeWhatsAppAction(Lead $lead, string $optionField): void
    {
        $campaign = $lead->campaign;

        // NUEVA LÓGICA: Usar whatsappSource
        if ($campaign->whatsappSource) {
            $source = $campaign->whatsappSource;

            // Validar que la fuente esté activa
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

            // Obtener el mensaje/plantilla configurado para esta opción
            $messageBody = $this->getMessageForOption($campaign, $optionField);

            if (! $messageBody) {
                Log::warning('No hay mensaje configurado para esta opción', [
                    'lead_id' => $lead->id,
                    'option_field' => $optionField,
                ]);

                return;
            }

            // Enviar mensaje usando la fuente
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

                // Registrar interacción outbound
                LeadInteraction::create([
                    'lead_id' => $lead->id,
                    'campaign_id' => $lead->campaign_id,
                    'channel' => InteractionChannel::WHATSAPP,
                    'direction' => InteractionDirection::OUTBOUND,
                    'content' => $messageBody,
                    'payload' => [
                        'source_id' => $source->id,
                        'option_field' => $optionField,
                        'result' => $result,
                    ],
                    'phone' => $lead->phone,
                ]);

                // Actualizar estado de intención del lead
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
            // LEGACY: Si no hay whatsappSource, usar config antigua
            // TODO: Deprecar esta lógica una vez migradas todas las campañas
            Log::warning('Campaña usa configuración legacy de WhatsApp (sin Source)', [
                'lead_id' => $lead->id,
                'campaign_id' => $campaign->id,
            ]);

            // Aquí iría la lógica antigua si existiera
            throw new ValidationException(
                'Campaña no tiene fuente de WhatsApp configurada. ' .
                    'Configure una Source de tipo WhatsApp para esta campaña.'
            );
        }
    }

    /**
     * Ejecutar acción de envío a webhook/CRM
     * Ahora usa LeadExportService para respetar reglas de exportación
     */
    protected function executeWebhookAction(Lead $lead): void
    {
        Log::info('Ejecutando acción de exportación', [
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'intention' => $lead->intention,
            'intention_status' => $lead->intention_status?->value,
        ]);

        // Usar LeadExportService que evalúa las reglas de exportación
        $exported = $this->leadExportService->exportLead($lead);

        if (! $exported) {
            Log::info('Lead no se exportó según reglas de campaña', [
                'lead_id' => $lead->id,
                'campaign_type' => $lead->campaign->campaign_type?->value,
                'export_rule' => $lead->campaign->export_rule?->value,
            ]);
        }
    }

    /**
     * Ejecutar acción de llamada con IA
     */
    protected function executeCallAIAction(Lead $lead): void
    {
        Log::info('Ejecutando acción de llamada con IA', [
            'lead_id' => $lead->id,
        ]);

        // TODO: Implementar lógica de llamada con IA
        // Esto puede disparar un job que use un CallProvider (Retell, etc.)
        throw new \Exception('Acción de llamada con IA no implementada aún');
    }

    /**
     * Marcar lead para revisión manual
     */
    protected function executeManualReviewAction(Lead $lead): void
    {
        Log::info('Lead marcado para revisión manual', [
            'lead_id' => $lead->id,
        ]);

        // TODO: Actualizar estado del lead o crear tarea de revisión
        // Por ahora solo registramos
    }

    /**
     * Obtener mensaje configurado para una opción
     *
     * Por ahora retorna un mensaje genérico, pero podría:
     * - Buscar en campaign->option_2_message, option_i_message, etc.
     * - Buscar template de WhatsApp asociado
     * - Generar mensaje dinámico
     */
    protected function getMessageForOption($campaign, string $optionField): ?string
    {
        // Mapear option field a message field
        $messageField = match ($optionField) {
            'option_2_action' => 'option_2_message',
            'option_i_action' => 'option_i_message',
            default => null,
        };

        if ($messageField && $campaign->{$messageField}) {
            return $campaign->{$messageField};
        }

        // Fallback: mensaje genérico
        return 'Gracias por tu interés. Un asesor se contactará contigo pronto.';
    }
}
