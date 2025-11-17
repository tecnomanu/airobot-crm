<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\CallWebhookRequest;
use App\Http\Requests\Webhook\LeadWebhookRequest;
use App\Http\Resources\CallHistory\CallHistoryResource;
use App\Http\Resources\Lead\LeadResource;
use App\Http\Traits\ApiResponse;
use App\Services\CallHistory\CallHistoryService;
use App\Services\Lead\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para webhooks entrantes de fuentes externas
 * (n8n, proveedores de telefonía, formularios, etc.)
 */
class WebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LeadService $leadService,
        private CallHistoryService $callHistoryService
    ) {}

    /**
     * Recibir lead desde webhook externo (n8n, formularios, etc.)
     * POST /api/webhooks/lead
     *
     * Campos esperados:
     * - phone (requerido)
     * - name
     * - city
     * - campaign (slug de la campaña)
     * - option_selected (1, 2, i, t)
     * - source (webhook_inicial, whatsapp, etc.)
     * - intention
     * - notes
     */
    public function receiveLead(LeadWebhookRequest $request): JsonResponse
    {
        try {
            Log::info('Webhook de lead recibido', [
                'phone' => $request->input('phone'),
                'campaign_pattern' => $request->input('campaign_pattern'),
                'source_ip' => $request->ip(),
            ]);

            // Preparar datos del lead
            $leadData = [
                'phone' => $request->input('phone'),
                'name' => $request->input('name'),
                'city' => $request->input('city'),
                'option_selected' => $request->input('option_selected'),
                'campaign_id' => $request->input('campaign_id'),
                'campaign' => $request->input('campaign') ?? $request->input('campaign_pattern'),  // Slug de campaña
                'source' => $request->input('source'),
                'intention' => $request->input('intention'),
                'notes' => $request->input('notes'),
            ];

            // Procesar lead (crear o actualizar)
            $lead = $this->leadService->processIncomingWebhookLead($leadData);

            Log::info('Lead procesado exitosamente desde webhook', [
                'lead_id' => $lead->id,
                'phone' => $lead->phone,
                'campaign_id' => $lead->campaign_id,
                'is_new' => $lead->wasRecentlyCreated,
            ]);

            $statusCode = $lead->wasRecentlyCreated ? 201 : 200;

            return $this->successResponse(
                new LeadResource($lead->load('campaign')),
                'Lead received and processed successfully',
                ['is_new' => $lead->wasRecentlyCreated],
                $statusCode
            );
        } catch (\InvalidArgumentException $e) {
            Log::warning('Error de validación en webhook de lead', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return $this->validationErrorResponse('Validation error', [$e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Error procesando webhook de lead', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return $this->serverErrorResponse(
                'Error processing lead',
                config('app.debug') ? $e->getMessage() : ''
            );
        }
    }

    /**
     * Recibir registro de llamada desde proveedor externo (Vapi, Retell, etc.)
     * POST /api/webhooks/call
     *
     * Campos esperados:
     * - phone (requerido)
     * - status (requerido)
     * - duration_seconds
     * - cost
     * - campaign_id o campaign_name
     * - client_id o client_name
     * - lead_id
     * - provider
     * - call_id_external
     * - recording_url
     * - transcript
     * - notes
     */
    public function receiveCall(CallWebhookRequest $request): JsonResponse
    {
        try {
            Log::info('Webhook de llamada recibido', [
                'phone' => $request->input('phone'),
                'provider' => $request->input('provider'),
                'status' => $request->input('status'),
                'source_ip' => $request->ip(),
            ]);

            // Preparar datos de la llamada
            $callData = [
                'phone' => $request->input('phone'),
                'call_id_external' => $request->input('call_id_external'),
                'provider' => $request->input('provider'),
                'status' => $request->input('status'),
                'duration_seconds' => $request->input('duration_seconds', 0),
                'cost' => $request->input('cost', 0),
                'campaign_id' => $request->input('campaign_id'),
                'client_id' => $request->input('client_id'),
                'lead_id' => $request->input('lead_id'),
                'notes' => $request->input('notes'),
                'recording_url' => $request->input('recording_url'),
                'transcript' => $request->input('transcript'),
                'call_date' => $request->input('call_date') ?
                    \Carbon\Carbon::parse($request->input('call_date')) :
                    now(),
            ];

            // Registrar llamada
            $call = $this->callHistoryService->registerIncomingCall($callData);

            Log::info('Llamada registrada exitosamente desde webhook', [
                'call_id' => $call->id,
                'phone' => $call->phone,
                'status' => $call->status->value,
                'duration' => $call->duration_seconds,
            ]);

            return $this->createdResponse(
                new CallHistoryResource($call->load(['campaign', 'client', 'lead'])),
                'Call received and processed successfully'
            );
        } catch (\InvalidArgumentException $e) {
            Log::warning('Error de validación en webhook de llamada', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return $this->validationErrorResponse('Validation error', [$e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Error procesando webhook de llamada', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return $this->serverErrorResponse(
                'Error processing call',
                config('app.debug') ? $e->getMessage() : ''
            );
        }
    }
}
