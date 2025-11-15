<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\CallProvider\CallProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para recibir webhooks de proveedores de llamadas
 * (Retell, Vapi, etc.)
 */
class CallProviderWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CallProviderManager $callProviderManager
    ) {}

    /**
     * Webhook para Retell AI
     * POST /api/webhooks/retell-call
     */
    public function retellWebhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $headers = $request->headers->all();
            $rawBody = $request->getContent();

            // Procesar webhook
            $callHistory = $this->callProviderManager->processWebhook(
                providerName: 'retell',
                payload: $payload,
                headers: $headers,
                rawBody: $rawBody
            );

            return $this->successResponse(
                ['call_id' => $callHistory->id],
                'Webhook processed successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error processing Retell webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return $this->serverErrorResponse('Error processing webhook', $e->getMessage());
        }
    }

    /**
     * Webhook para Vapi (futuro)
     * POST /api/webhooks/vapi-call
     */
    public function vapiWebhook(Request $request): JsonResponse
    {
        return $this->errorResponse('Vapi provider not implemented yet', '', 501);
    }

    /**
     * Webhook genérico con provider en path
     * POST /api/webhooks/call/{provider}
     */
    public function genericWebhook(Request $request, string $provider): JsonResponse
    {
        try {
            $payload = $request->all();
            $headers = $request->headers->all();
            $rawBody = $request->getContent();

            $callHistory = $this->callProviderManager->processWebhook(
                providerName: $provider,
                payload: $payload,
                headers: $headers,
                rawBody: $rawBody
            );

            return $this->successResponse(
                ['call_id' => $callHistory->id],
                'Webhook processed successfully'
            );

        } catch (\Exception $e) {
            Log::error("Error processing {$provider} webhook", [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverErrorResponse('Error processing webhook', $e->getMessage());
        }
    }
}
