<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\WhatsApp\WhatsAppIncomingMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para recibir webhooks de Evolution API (WhatsApp)
 * 
 * Este webhook recibe las respuestas de los leads cuando responden
 * mensajes de WhatsApp, guarda la interacción y actualiza la intención del lead.
 */
class WhatsAppWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WhatsAppIncomingMessageService $whatsappService
    ) {}

    /**
     * Webhook principal para Evolution API
     * POST /api/webhooks/whatsapp/evolution
     * 
     * Evolution API envía webhooks cuando:
     * - Se recibe un mensaje de un contacto
     * - Se actualiza el estado de un mensaje enviado
     * - Otros eventos de la API
     */
    public function evolutionWebhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $headers = $request->headers->all();

            Log::info('Webhook recibido de Evolution API', [
                'event' => $payload['event'] ?? 'unknown',
                'instance' => $payload['instance'] ?? null,
                'has_data' => isset($payload['data']),
            ]);

            // Verificar que sea un mensaje entrante
            $event = $payload['event'] ?? null;
            
            if (!in_array($event, ['messages.upsert', 'messages.update'])) {
                return $this->successResponse(
                    ['processed' => false],
                    'Event not processed - not a message event'
                );
            }

            // Procesar el mensaje
            $result = $this->whatsappService->processIncomingMessage($payload);

            if (!$result) {
                return $this->successResponse(
                    ['processed' => false],
                    'Message not processed - possibly not from a lead'
                );
            }

            return $this->successResponse(
                [
                    'processed' => true,
                    'lead_id' => $result['lead_id'] ?? null,
                    'interaction_id' => $result['interaction_id'] ?? null,
                    'auto_reply_sent' => $result['auto_reply_sent'] ?? false,
                ],
                'Message processed successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error procesando webhook de Evolution API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            // Responder con éxito para que Evolution no reintente
            // pero logear el error
            return $this->successResponse(
                ['processed' => false, 'error' => 'Internal error logged'],
                'Webhook received but processing failed'
            );
        }
    }

    /**
     * Webhook genérico para otros proveedores de WhatsApp (futuro)
     * POST /api/webhooks/whatsapp/{provider}
     */
    public function genericWhatsAppWebhook(Request $request, string $provider): JsonResponse
    {
        return $this->errorResponse(
            "WhatsApp provider '$provider' not implemented yet",
            '',
            501
        );
    }
}

