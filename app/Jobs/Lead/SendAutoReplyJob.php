<?php

namespace App\Jobs\Lead;

use App\Contracts\WhatsAppSenderInterface;
use App\Enums\MessageDirection;
use App\Models\Lead\Lead;
use App\Services\Lead\LeadMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Job for sending auto-reply with debouncing
 */
class SendAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 30;

    public function __construct(
        private string $leadId,
        private int $expectedVersion
    ) {}

    public function handle(
        WhatsAppSenderInterface $whatsappSender,
        LeadMessageService $messageService
    ): void {
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
            $autoReplyMessage = 'Gracias por tu mensaje. Un asesor revisará tu consulta y te responderá a la brevedad. 📱';

            $campaign = $lead->campaign;
            if (! $campaign) {
                Log::warning('Lead sin campaña, no se puede enviar auto-respuesta', [
                    'lead_id' => $lead->id,
                ]);

                return;
            }

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

            $whatsappSender->sendMessage($source, $lead, $autoReplyMessage, []);

            // Save auto-reply using LeadMessageService
            $messageService->createFromWhatsAppMessage(
                leadId: $lead->id,
                campaignId: $lead->campaign_id,
                content: $autoReplyMessage,
                metadata: ['type' => 'auto_reply'],
                externalProviderId: null,
                phone: $lead->phone,
                direction: MessageDirection::OUTBOUND
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
            Cache::forget($cacheKey);
        }
    }
}
