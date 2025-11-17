<?php

namespace App\Services\WhatsApp;

use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Models\Lead;
use App\Models\LeadInteraction;
use App\Repositories\Interfaces\LeadRepositoryInterface;
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
        private LeadRepositoryInterface $leadRepository,
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
        // Priorizar remoteJidAlt (número real) sobre remoteJid (puede ser LID interno)
        $remoteJid = $key['remoteJidAlt'] ?? $key['remoteJid'] ?? null;
        
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

        // Buscar lead por teléfono
        $lead = $this->findLeadByPhone($phone);

        if (! $lead) {
            Log::warning('Lead no encontrado para número', [
                'phone' => $phone,
                'message' => $messageContent,
            ]);

            return null;
        }

        // Actualizar datos de contacto desde WhatsApp si están disponibles
        $this->updateLeadContactInfo($lead, $data);

        // Guardar interacción
        $interaction = $this->saveInteraction($lead, $messageContent, $payload, $key['id'] ?? null);

        // Actualizar intención del lead
        $this->updateLeadIntention($lead, $messageContent);

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
            $phone = '+'.$phone;
        }

        // Usar PhoneHelper para normalización completa (AR por defecto)
        $normalized = \App\Helpers\PhoneHelper::normalizeWithCountry($phone, 'AR');

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
            return '[Imagen] '.$message['imageMessage']['caption'];
        }

        if (isset($message['videoMessage']['caption'])) {
            return '[Video] '.$message['videoMessage']['caption'];
        }

        if (isset($message['documentMessage'])) {
            return '[Documento] '.($message['documentMessage']['fileName'] ?? 'archivo');
        }

        if (isset($message['audioMessage'])) {
            return '[Audio]';
        }

        return null;
    }

    /**
     * Buscar lead por número de teléfono
     *
     * Evolution envía números sin el + y para Argentina a veces sin el 9
     * Ej: 542944636430@s.whatsapp.net → +542944636430 en nuestra DB
     */
    protected function findLeadByPhone(string $phone): ?Lead
    {
        // Buscar con el formato exacto
        $lead = Lead::where('phone', $phone)->first();

        if ($lead) {
            return $lead;
        }

        // Limpiar número para búsqueda flexible
        $cleanPhone = str_replace(['+', ' ', '-'], '', $phone);

        // Buscar variantes comunes
        $lead = Lead::where(function ($query) use ($cleanPhone, $phone) {
            $query->where('phone', $phone)
                ->orWhere('phone', '+'.$cleanPhone)
                ->orWhere('phone', $cleanPhone);

            // Si empieza con 549, también buscar sin el 9 (Argentina)
            if (str_starts_with($cleanPhone, '549')) {
                $withoutNine = '54'.substr($cleanPhone, 3);
                $query->orWhere('phone', '+'.$withoutNine)
                    ->orWhere('phone', $withoutNine);
            }

            // Si empieza con 54 (sin 9), también buscar con el 9
            if (str_starts_with($cleanPhone, '54') && ! str_starts_with($cleanPhone, '549')) {
                $withNine = '549'.substr($cleanPhone, 2);
                $query->orWhere('phone', '+'.$withNine)
                    ->orWhere('phone', $withNine);
            }
        })->first();

        return $lead;
    }

    /**
     * Guardar interacción en base de datos
     */
    protected function saveInteraction(
        Lead $lead,
        string $content,
        array $payload,
        ?string $externalId
    ): LeadInteraction {
        return LeadInteraction::create([
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'channel' => InteractionChannel::WHATSAPP,
            'direction' => InteractionDirection::INBOUND,
            'content' => $content,
            'payload' => $payload,
            'external_id' => $externalId,
            'phone' => $lead->phone,
        ]);
    }

    /**
     * Actualizar intención del lead basado en su respuesta
     */
    protected function updateLeadIntention(Lead $lead, string $messageContent): void
    {
        // Analizar palabras clave para determinar intención
        $intention = $this->analyzeIntention($messageContent);

        // Concatenar con la intención anterior si existe
        $previousIntention = $lead->intention ?? '';

        $newIntention = $previousIntention
            ? $previousIntention."\n[".now()->format('Y-m-d H:i').'] '.$messageContent
            : '['.now()->format('Y-m-d H:i').'] '.$messageContent;

        // Limitar longitud para no llenar demasiado la DB
        if (strlen($newIntention) > 2000) {
            // Mantener solo los últimos 2000 caracteres
            $newIntention = '...'.substr($newIntention, -1997);
        }

        // Actualizar lead con intención detectada
        $updateData = [
            'intention' => $intention ?: $newIntention,
        ];

        // Si detectamos una intención clara, finalizar el intent
        if ($intention) {
            $updateData['intention_status'] = LeadIntentionStatus::FINALIZED;
            $updateData['intention_decided_at'] = now();

            // Si no tenía origin, asignar WhatsApp
            if (! $lead->intention_origin) {
                $updateData['intention_origin'] = LeadIntentionOrigin::WHATSAPP;
            }
        }

        $lead->update($updateData);

        Log::info('Intención del lead actualizada', [
            'lead_id' => $lead->id,
            'detected_intention' => $intention,
            'new_content_length' => strlen($messageContent),
            'finalized' => (bool) $intention,
        ]);
    }

    /**
     * Analizar contenido del mensaje para detectar intención
     */
    protected function analyzeIntention(string $content): ?string
    {
        $contentLower = mb_strtolower($content);

        // Palabras clave para "interested"
        $interestedKeywords = [
            'sí', 'si', 'yes', 'interesado', 'interesada', 'quiero', 'me interesa',
            'info', 'información', 'mas info', 'más info', 'dame', 'llamame',
            'llámame', 'contactame', 'contáctame', 'ok', 'dale', 'perfecto',
        ];

        foreach ($interestedKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return 'interested';
            }
        }

        // Palabras clave para "not_interested"
        $notInterestedKeywords = [
            'no', 'nope', 'no gracias', 'no me interesa', 'no quiero',
            'no estoy interesado', 'no estoy interesada', 'baja', 'borrar',
            'eliminar', 'remover', 'stop', 'cancelar', 'no molesten',
        ];

        foreach ($notInterestedKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return 'not_interested';
            }
        }

        // Si no detectamos intención clara, retornar null
        // El sistema seguirá esperando más mensajes
        return null;
    }

    /**
     * Actualizar información de contacto del lead desde datos de WhatsApp
     */
    protected function updateLeadContactInfo(Lead $lead, array $data): void
    {
        // Extraer pushName (nombre del contacto en WhatsApp)
        $pushName = $data['pushName'] ?? null;

        // Solo actualizar si:
        // 1. Hay pushName disponible
        // 2. El lead no tiene nombre O tiene un nombre genérico/placeholder
        if ($pushName && $this->shouldUpdateName($lead, $pushName)) {
            $lead->update([
                'name' => $pushName,
            ]);

            Log::info('Nombre del lead actualizado desde WhatsApp', [
                'lead_id' => $lead->id,
                'old_name' => $lead->name,
                'new_name' => $pushName,
            ]);
        }
    }

    /**
     * Determinar si debemos actualizar el nombre del lead
     */
    protected function shouldUpdateName(Lead $lead, string $newName): bool
    {
        // Si no tiene nombre, actualizar
        if (empty($lead->name)) {
            return true;
        }

        // Lista de nombres genéricos/placeholder que pueden sobrescribirse
        $genericNames = [
            'Lead sin nombre',
            'Sin nombre',
            'Unknown',
            'N/A',
            'lead',
        ];

        $currentName = trim(strtolower($lead->name));

        foreach ($genericNames as $generic) {
            if (str_contains($currentName, strtolower($generic))) {
                return true;
            }
        }

        // Si el nombre actual es solo el teléfono, actualizar
        if ($lead->name === $lead->phone) {
            return true;
        }

        // No sobrescribir nombres reales
        return false;
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
            LeadInteraction::create([
                'lead_id' => $lead->id,
                'campaign_id' => $lead->campaign_id,
                'channel' => InteractionChannel::WHATSAPP,
                'direction' => InteractionDirection::OUTBOUND,
                'content' => $autoReplyMessage,
                'payload' => ['type' => 'auto_reply'],
                'external_id' => null,
                'phone' => $lead->phone,
            ]);

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
