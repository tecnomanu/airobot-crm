<?php

namespace App\Services\WhatsApp;

use App\Enums\InteractionDirection;
use App\Helpers\PhoneHelper;
use App\Models\Lead;
use App\Services\Lead\LeadInteractionService;
use App\Services\Lead\LeadService;
use App\Services\WhatsAppSenderService;
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
        private WhatsAppSenderService $whatsappSender
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

        // Enviar respuesta automática
        $autoReplySent = $this->sendAutoReply($lead, $instance);

        return [
            'lead_id' => $lead->id,
            'interaction_id' => $interaction->id,
            'auto_reply_sent' => $autoReplySent,
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
     * Enviar respuesta automática al lead
     */
    protected function sendAutoReply(Lead $lead, ?string $instance): bool
    {
        try {
            // TODO: Hacer esto configurable por campaña
            $autoReplyMessage = 'Gracias por tu mensaje. Un asesor revisará tu consulta y te responderá a la brevedad. 📱';

            // Obtener la fuente de WhatsApp de la campaña
            $campaign = $lead->campaign;
            if (! $campaign) {
                Log::warning('Lead sin campaña, no se puede enviar auto-respuesta', [
                    'lead_id' => $lead->id,
                ]);

                return false;
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

                return false;
            }

            $source = $whatsappOption->source;

            // Enviar mensaje
            $this->whatsappSender->sendMessage($source, $lead, $autoReplyMessage);

            // Guardar la respuesta automática como interacción saliente
            $this->interactionService->createFromWhatsAppMessage(
                leadId: $lead->id,
                campaignId: $lead->campaign_id,
                content: $autoReplyMessage,
                payload: ['type' => 'auto_reply'],
                externalId: null,
                phone: $lead->phone,
                direction: InteractionDirection::OUTBOUND
            );

            Log::info('Auto-respuesta enviada exitosamente', [
                'lead_id' => $lead->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error enviando auto-respuesta', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
