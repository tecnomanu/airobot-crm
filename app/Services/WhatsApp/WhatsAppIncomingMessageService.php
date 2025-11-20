<?php

namespace App\Services\WhatsApp;

use App\Enums\InteractionDirection;
use App\Helpers\PhoneHelper;
use App\Models\Lead;
use App\Contracts\WhatsAppSenderInterface;
use App\Services\Lead\LeadInteractionService;
use App\Services\Lead\LeadService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para procesar mensajes entrantes de WhatsApp (Evolution API)
 *
 * - Identifica el lead por número de teléfono
 * - Guarda la interacción (mensaje del lead)
 * - Actualiza la intención del lead
 * - Envía respuesta automática configurable
 * - Mantiene historial del chat
 */
class WhatsAppIncomingMessageService
{
    public function __construct(
        private LeadService $leadService,
        private LeadInteractionService $interactionService,
        private WhatsAppSenderInterface $whatsappSender
    ) {}

    /**
     * Procesar mensaje entrante desde Evolution API
     *
     * @param  array  $payload  Payload completo del webhook
     * @return array|null Resultado del procesamiento
     */
    public function processIncomingMessage(array $payload): ?array
    {
        // Extraer datos del mensaje según estructura de Evolution API
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $instance = $payload['instance'] ?? null;

        // Solo procesar mensajes entrantes (no enviados por nosotros)
        $key = $data['key'] ?? [];
        $isFromMe = $key['fromMe'] ?? true;

        if ($isFromMe) {
            Log::debug('Mensaje ignorado: enviado por nosotros', [
                'message_id' => $key['id'] ?? null,
            ]);

            return null;
        }

        // Extraer información del contacto
        // Usar remoteJid (número real formato @s.whatsapp.net)
        // Solo usar remoteJidAlt si remoteJid no existe o es un LID
        $remoteJid = $key['remoteJid'] ?? null;

        // Si remoteJid es un LID (@lid), usar el número real si está disponible
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

        // Limpiar número (Evolution envía como 5492944636430@s.whatsapp.net)
        $phone = $this->normalizePhone($remoteJid);

        // Extraer contenido del mensaje
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

        // Buscar o crear lead por teléfono
        $lead = $this->leadService->findOrCreateFromWhatsApp($phone, $data);

        // Actualizar datos de contacto desde WhatsApp si están disponibles
        $this->leadService->updateContactInfoFromWhatsApp($lead, $data);

        // Guardar interacción
        $interaction = $this->interactionService->createFromWhatsAppMessage(
            leadId: $lead->id,
            campaignId: $lead->campaign_id,
            content: $messageContent,
            payload: $payload,
            externalId: $key['id'] ?? null,
            phone: $lead->phone,
            direction: InteractionDirection::INBOUND
        );

        // Actualizar intención del lead
        $this->leadService->updateIntentionFromMessage($lead, $messageContent);

        // Programar respuesta automática con debouncing (espera a que termine de escribir)
        $this->scheduleAutoReply($lead);

        return [
            'lead_id' => $lead->id,
            'interaction_id' => $interaction->id,
            'auto_reply_scheduled' => true,
        ];
    }

    /**
     * Normalizar número de teléfono desde formato Evolution
     * 5492944636430@s.whatsapp.net → +5492944636430
     */
    protected function normalizePhone(string $remoteJid): string
    {
        // Extraer solo el número
        $phone = explode('@', $remoteJid)[0];

        Log::info('🔍 DEBUG - Normalizando teléfono', [
            'remoteJid_original' => $remoteJid,
            'phone_extraido' => $phone,
        ]);

        // Agregar + si no lo tiene
        if (! str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        // Usar PhoneHelper para normalización completa (AR por defecto)
        $normalized = PhoneHelper::normalizeWithCountry($phone, 'AR');

        Log::info('🔍 DEBUG - Teléfono normalizado', [
            'phone_con_plus' => $phone,
            'phone_normalizado' => $normalized,
        ]);

        return $normalized;
    }

    /**
     * Extraer contenido de texto del mensaje
     */
    protected function extractMessageContent(array $message): ?string
    {
        // Evolution API puede enviar diferentes tipos de mensajes
        // conversation: mensaje simple de texto
        // extendedTextMessage: mensaje con formato/links
        // imageMessage: imagen con caption

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

    /**
     * Programar respuesta automática con debouncing
     *
     * Si el lead envía múltiples mensajes rápidos, solo se enviará UNA respuesta
     * después de que pasen X segundos sin nuevos mensajes.
     */
    protected function scheduleAutoReply(Lead $lead): void
    {
        $delaySeconds = config('services.whatsapp.auto_reply_delay', 5);

        // Incrementar versión para invalidar jobs anteriores (debouncing)
        $cacheKey = "auto_reply:{$lead->id}";
        
        // Obtener versión actual o inicializar en 0
        $currentVersion = Cache::get($cacheKey, 0);
        $newVersion = $currentVersion + 1;
        
        // Guardar nueva versión
        Cache::put($cacheKey, $newVersion, now()->addMinutes(10));

        // Programar job con delay
        // Si llega otro mensaje, se incrementará la versión y este job se cancelará
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
