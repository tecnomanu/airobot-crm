<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\WebhookEventRequest;
use App\Http\Traits\ApiResponse;
use App\Services\Webhook\WebhookEventManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para webhooks basados en eventos con patrón Strategy
 * 
 * Endpoints:
 * - POST /api/webhooks/event - Procesa eventos dinámicos
 * - GET /api/webhooks/events - Lista eventos disponibles
 */
class WebhookEventController extends Controller
{
    use ApiResponse;
    public function __construct(
        private WebhookEventManager $eventManager
    ) {}

    /**
     * Procesa un evento de webhook dinámico
     * 
     * POST /api/webhooks/event
     * 
     * Body:
     * {
     *   "name": "webhook_register_phone",
     *   "args": {
     *     "phone": "2944636430",
     *     "name": "Manuel",
     *     "city": "Buenos Aires",
     *     "option_selected": "1",
     *     "campaign": "direct-tv"
     *   }
     * }
     */
    public function handleEvent(WebhookEventRequest $request): JsonResponse
    {
        $eventName = $request->input('name');
        $args = $request->input('args');

        Log::info('Webhook event received', [
            'event' => $eventName,
            'args_keys' => array_keys($args),
            'source_ip' => $request->ip(),
        ]);

        // Despachar a la estrategia correspondiente
        return $this->eventManager->dispatch($eventName, $args);
    }

    /**
     * Lista los eventos disponibles
     * 
     * GET /api/webhooks/events
     */
    public function listEvents(): JsonResponse
    {
        $events = $this->eventManager->getAvailableEvents();

        return $this->successResponse(
            $events,
            'Available webhook events',
            ['total' => count($events)]
        );
    }
}

