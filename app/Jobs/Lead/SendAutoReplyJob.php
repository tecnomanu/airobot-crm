<?php

namespace App\Jobs\Lead;

use App\Contracts\WhatsAppSenderInterface;
use App\Enums\InteractionDirection;
use App\Models\Lead;
use App\Services\Lead\LeadInteractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar respuesta automática con debouncing
 *
 * Si el lead envía múltiples mensajes rápidos, solo se enviará
 * UNA respuesta después de que pasen X segundos sin nuevos mensajes.
 */
class SendAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 30;

    /**
     * @param  string  $leadId  ID del lead
     * @param  int  $expectedVersion  Versión esperada para debouncing
     */
    public function __construct(
        private string $leadId,
        private int $expectedVersion
    ) {}

    public function handle(
        WhatsAppSenderInterface $whatsappSender,
        LeadInteractionService $interactionService
    ): void {
        // Verificar versión (debouncing)
        $cacheKey = "auto_reply:{$this->leadId}";
        $currentVersion = Cache::get($cacheKey, 0);

        if ($currentVersion !== $this->expectedVersion) {
            Log::info('Job de auto-respuesta cancelado (versión desactualizada)', [
                'lead_id' => $this->leadId,
                'expected_version' => $this->expectedVersion,
                'current_version' => $currentVersion,
            ]);

            return;
        }

        $lead = Lead::with('campaign.options.source')->find($this->leadId);

        if (! $lead) {
            Log::warning('Lead no encontrado para auto-respuesta', [
                'lead_id' => $this->leadId,
            ]);

            return;
        }

        try {
            // TODO: Hacer mensaje configurable por campaña
            $autoReplyMessage = 'Gracias por tu mensaje. Un asesor revisará tu consulta y te responderá a la brevedad. 📱';

            // Obtener la fuente de WhatsApp de la campaña
            $campaign = $lead->campaign;
            if (! $campaign) {
                Log::warning('Lead sin campaña, no se puede enviar auto-respuesta', [
                    'lead_id' => $lead->id,
                ]);

                return;
            }

            // Buscar source de WhatsApp usado en las opciones de la campaña
            $whatsappOption = $campaign->options()
                ->where('action', 'whatsapp')
                ->whereNotNull('source_id')
                ->first();

            if (! $whatsappOption || ! $whatsappOption->source) {
                Log::warning('Campaña sin fuente de WhatsApp configurada', [
                    'campaign_id' => $campaign->id,
                ]);

                return;
            }

            $source = $whatsappOption->source;

            // Enviar mensaje
            $whatsappSender->sendMessage($source, $lead, $autoReplyMessage, []);

            // Guardar la respuesta automática como interacción saliente
            $interactionService->createFromWhatsAppMessage(
                leadId: $lead->id,
                campaignId: $lead->campaign_id,
                content: $autoReplyMessage,
                payload: ['type' => 'auto_reply'],
                externalId: null,
                phone: $lead->phone,
                direction: InteractionDirection::OUTBOUND
            );

            Log::info('Auto-respuesta enviada exitosamente (debounced)', [
                'lead_id' => $lead->id,
                'version' => $this->expectedVersion,
            ]);
        } catch (\Exception $e) {
            Log::error('Error enviando auto-respuesta', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Limpiar cache
            Cache::forget($cacheKey);
        }
    }
}
