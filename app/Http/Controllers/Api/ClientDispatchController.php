<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\SendLeadToClientRequest;
use App\Http\Traits\ApiResponse;
use App\Jobs\SendLeadToClientWebhook;
use App\Services\Lead\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para enviar leads a webhooks de clientes
 * Usado típicamente desde n8n, automatizaciones internas o panel admin
 */
class ClientDispatchController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LeadService $leadService
    ) {}

    /**
     * Enviar un lead al webhook del cliente asociado a su campaña
     * POST /api/clients/{client}/leads/{lead}/dispatch
     * 
     * Opciones:
     * - force_resend: boolean (reenviar aunque ya se haya enviado)
     * - custom_payload: object (payload personalizado)
     * - async: boolean (enviar de forma asíncrona con Job)
     */
    public function dispatch(SendLeadToClientRequest $request, int $clientId, int $leadId): JsonResponse
    {
        try {
            $lead = $this->leadService->getLeadById($leadId);

            if (!$lead) {
                return $this->notFoundResponse('Lead not found');
            }

            // Validar que el lead pertenece a una campaña del cliente especificado
            if ($lead->campaign->client_id !== $clientId) {
                return $this->forbiddenResponse('Lead does not belong to specified client');
            }

            $campaign = $lead->campaign;

            // Validar que el webhook está habilitado
            if (!$campaign->webhook_enabled) {
                return $this->errorResponse(
                    'Webhook is not enabled for this campaign',
                    '',
                    400
                );
            }

            // Validar URL de webhook
            if (empty($campaign->webhook_url)) {
                return $this->errorResponse(
                    'Webhook URL is not configured for this campaign',
                    '',
                    400
                );
            }

            // Validar si ya fue enviado (a menos que force_resend esté activado)
            if ($lead->webhook_sent && !$request->input('force_resend', false)) {
                return $this->errorResponse(
                    'Lead has already been sent to client. Use force_resend=true to retry.',
                    '',
                    400,
                    ['previous_result' => json_decode($lead->webhook_result, true)]
                );
            }

            Log::info('Dispatch de lead al cliente solicitado', [
                'lead_id' => $leadId,
                'client_id' => $clientId,
                'campaign_id' => $campaign->id,
                'force_resend' => $request->input('force_resend', false),
                'async' => $request->input('async', true),
            ]);

            // Enviar de forma asíncrona (recomendado) o síncrona
            $async = $request->input('async', true);

            if ($async) {
                // Encolar job con reintentos automáticos
                SendLeadToClientWebhook::dispatch($lead, $request->input('custom_payload'))
                    ->onQueue('webhooks');

                return $this->successResponse(
                    [
                        'lead_id' => $leadId,
                        'webhook_url' => $campaign->webhook_url,
                        'async' => true,
                    ],
                    'Lead dispatch queued successfully',
                    [],
                    202
                );
            }

            // Envío síncrono (no recomendado para producción)
            try {
                SendLeadToClientWebhook::dispatchSync($lead, $request->input('custom_payload'));

                // Recargar lead para obtener resultado actualizado
                $lead->refresh();

                $result = json_decode($lead->webhook_result, true);
                $success = isset($result['status_code']) && $result['status_code'] >= 200 && $result['status_code'] < 300;

                if ($success) {
                    return $this->successResponse(
                        ['result' => $result, 'async' => false],
                        'Lead sent successfully'
                    );
                } else {
                    return $this->serverErrorResponse(
                        'Lead sent but client returned error',
                        json_encode($result)
                    );
                }

            } catch (\Exception $e) {
                Log::error('Error en envío síncrono de lead', [
                    'lead_id' => $leadId,
                    'error' => $e->getMessage(),
                ]);

                return $this->serverErrorResponse('Error sending lead to client', $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Error en dispatch de lead al cliente', [
                'lead_id' => $leadId,
                'client_id' => $clientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverErrorResponse(
                'Error dispatching lead',
                config('app.debug') ? $e->getMessage() : ''
            );
        }
    }

    /**
     * Obtener estado de envío de un lead
     * GET /api/clients/{client}/leads/{lead}/dispatch-status
     */
    public function status(int $clientId, int $leadId): JsonResponse
    {
        try {
            $lead = $this->leadService->getLeadById($leadId);

            if (!$lead) {
                return $this->notFoundResponse('Lead not found');
            }

            if ($lead->campaign->client_id !== $clientId) {
                return $this->forbiddenResponse('Lead does not belong to specified client');
            }

            return $this->successResponse([
                'lead_id' => $leadId,
                'webhook_sent' => $lead->webhook_sent,
                'sent_at' => $lead->sent_at?->toIso8601String(),
                'result' => $lead->webhook_result ? json_decode($lead->webhook_result, true) : null,
            ], 'Dispatch status retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error retrieving dispatch status', $e->getMessage());
        }
    }
}
