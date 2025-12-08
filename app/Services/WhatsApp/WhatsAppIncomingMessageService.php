<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSenderInterface;
use App\Enums\MessageDirection;
use App\Helpers\PhoneHelper;
use App\Models\Lead\Lead;
use App\Services\Lead\LeadMessageService;
use App\Services\Lead\LeadService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for processing incoming WhatsApp messages (Evolution API)
 */
class WhatsAppIncomingMessageService
{
    public function __construct(
        private LeadService $leadService,
        private LeadMessageService $messageService,
        private WhatsAppSenderInterface $whatsappSender
    ) {}

    /**
     * Process incoming message from Evolution API
     */
    public function processIncomingMessage(array $payload): ?array
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $instance = $payload['instance'] ?? null;

        $key = $data['key'] ?? [];
        $isFromMe = $key['fromMe'] ?? true;

        if ($isFromMe) {
            Log::debug('Mensaje ignorado: enviado por nosotros', [
                'message_id' => $key['id'] ?? null,
            ]);

            return null;
        }

        $remoteJid = $key['remoteJid'] ?? null;

        if ($remoteJid && str_contains($remoteJid, '@lid')) {
            $remoteJid = $key['remoteJidAlt'] ?? $remoteJid;
        }

        if (! $remoteJid) {
            Log::warning('Mensaje sin remoteJid', ['data' => $data]);

            return null;
        }

        Log::info('🔍 DEBUG - RemoteJid detectado', [
            'remoteJid' => $key['remoteJid'] ?? null,
            'remoteJidAlt' => $key['remoteJidAlt'] ?? null,
            'usando' => $remoteJid,
        ]);

        $phone = $this->normalizePhone($remoteJid);

        $message = $data['message'] ?? [];
        $messageContent = $this->extractMessageContent($message);

        if (! $messageContent) {
            Log::debug('Mensaje sin contenido de texto', [
                'phone' => $phone,
                'message_type' => array_keys($message)[0] ?? 'unknown',
            ]);

            return null;
        }

        Log::info('Procesando mensaje entrante de WhatsApp', [
            'phone' => $phone,
            'content_preview' => substr($messageContent, 0, 50),
            'instance' => $instance,
        ]);

        // Find or create lead by phone
        $lead = $this->leadService->findOrCreateFromWhatsApp($phone, $data);

        // Update contact info from WhatsApp if available
        $this->leadService->updateContactInfoFromWhatsApp($lead, $data);

        // Save message using new LeadMessage model
        $leadMessage = $this->messageService->createFromWhatsAppMessage(
            leadId: $lead->id,
            campaignId: $lead->campaign_id,
            content: $messageContent,
            metadata: $payload,
            externalProviderId: $key['id'] ?? null,
            phone: $lead->phone,
            direction: MessageDirection::INBOUND
        );

        // Update lead intention
        $this->leadService->updateIntentionFromMessage($lead, $messageContent);

        // Schedule auto reply with debouncing
        $this->scheduleAutoReply($lead);

        return [
            'lead_id' => $lead->id,
            'message_id' => $leadMessage->id,
            'auto_reply_scheduled' => true,
        ];
    }

    protected function normalizePhone(string $remoteJid): string
    {
        $phone = explode('@', $remoteJid)[0];

        Log::info('🔍 DEBUG - Normalizando teléfono', [
            'remoteJid_original' => $remoteJid,
            'phone_extraido' => $phone,
        ]);

        if (! str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        $normalized = PhoneHelper::normalizeWithCountry($phone, 'AR');

        Log::info('🔍 DEBUG - Teléfono normalizado', [
            'phone_con_plus' => $phone,
            'phone_normalizado' => $normalized,
        ]);

        return $normalized;
    }

    protected function extractMessageContent(array $message): ?string
    {
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }

        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }

        if (isset($message['imageMessage']['caption'])) {
            return '[Imagen] ' . $message['imageMessage']['caption'];
        }

        if (isset($message['videoMessage']['caption'])) {
            return '[Video] ' . $message['videoMessage']['caption'];
        }

        if (isset($message['documentMessage'])) {
            return '[Documento] ' . ($message['documentMessage']['fileName'] ?? 'archivo');
        }

        if (isset($message['audioMessage'])) {
            return '[Audio]';
        }

        return null;
    }

    protected function scheduleAutoReply(Lead $lead): void
    {
        $delaySeconds = config('services.whatsapp.auto_reply_delay', 5);

        $cacheKey = "auto_reply:{$lead->id}";

        $currentVersion = Cache::get($cacheKey, 0);
        $newVersion = $currentVersion + 1;

        Cache::put($cacheKey, $newVersion, now()->addMinutes(10));

        $delay = now()->addSeconds($delaySeconds);

        \App\Jobs\Lead\SendAutoReplyJob::dispatch($lead->id, $newVersion)
            ->delay($delay)
            ->onQueue('default');

        Log::info('Job de auto-respuesta programado', [
            'lead_id' => $lead->id,
            'version' => $newVersion,
            'delay_seconds' => $delaySeconds,
        ]);
    }
}
