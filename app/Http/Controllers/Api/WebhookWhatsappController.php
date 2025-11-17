<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\WhatsApp\WhatsAppIncomingMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para recibir webhooks de WhatsApp (Evolution API)
 *
 * Webhook principal: POST /api/webhooks/whatsapp-incoming
 *
 * Responsabilidades:
 * - Recibir mensajes entrantes de leads
 * - Identificar lead por número
 * - Guardar interacción en base de datos
 * - Actualizar intención del lead
 * - Enviar respuesta automática
 */
class WebhookWhatsappController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WhatsAppIncomingMessageService $whatsappService
    ) {}

    /**
     * Webhook para Evolution API
     * POST /api/webhooks/whatsapp-incoming
     *
     * Evolution API envía diferentes eventos:
     * - messages.upsert: Nuevo mensaje recibido
     * - messages.update: Actualización de mensaje (leído, entregado, etc.)
     * - presence.update: Estado del contacto (online, escribiendo, etc.)
     *
     * Solo procesamos messages.upsert de leads (no mensajes nuestros)
     */
    public function incoming(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $event = $payload['event'] ?? null;

            Log::info('Webhook WhatsApp recibido', [
                'event' => $event,
                'instance' => $payload['instance'] ?? null,
                'has_data' => isset($payload['data']),
            ]);

            // Log completo del payload para debugging
            Log::info('Payload completo del webhook', [
                'full_payload' => $payload,
            ]);

            // Verificar que sea un evento de mensaje
            if (! in_array($event, ['messages.upsert', 'messages.update'])) {
                return $this->successResponse(
                    ['processed' => false],
                    'Event not processed - not a message event'
                );
            }

            // Procesar el mensaje
            $result = $this->whatsappService->processIncomingMessage($payload);

            if (! $result) {
                return $this->successResponse(
                    ['processed' => false],
                    'Message not processed - possibly not from a lead or sent by us'
                );
            }

            return $this->successResponse(
                [
                    'processed' => true,
                    'lead_id' => $result['lead_id'] ?? null,
                    'interaction_id' => $result['interaction_id'] ?? null,
                    'auto_reply_sent' => $result['auto_reply_sent'] ?? false,
                ],
                'Message processed and saved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error procesando webhook de WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            // Responder con éxito para que Evolution no reintente
            // pero logear el error internamente
            return $this->successResponse(
                ['processed' => false, 'error' => 'Internal error logged'],
                'Webhook received but processing failed'
            );
        }
    }
}
