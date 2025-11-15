<?php

namespace App\Services\Webhook\Strategies;

use App\Contracts\WebhookEventStrategyInterface;
use App\Http\Resources\Lead\LeadResource;
use App\Services\Lead\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Estrategia para procesar el evento "webhook_register_phone"
 * 
 * Este evento registra un nuevo lead desde una llamada IVR o chatbot.
 */
class RegisterPhoneEventStrategy implements WebhookEventStrategyInterface
{
    public function __construct(
        private LeadService $leadService
    ) {}

    public function getEventName(): string
    {
        return 'webhook_register_phone';
    }

    public function handle(array $args): JsonResponse
    {
        try {
            Log::info('Procesando evento webhook_register_phone', [
                'phone' => $args['phone'] ?? null,
                'name' => $args['name'] ?? null,
            ]);

            // Preparar datos del lead
            $leadData = [
                'phone' => $args['phone'],
                'name' => $args['name'] ?? null,
                'city' => $args['city'] ?? null,
                'option_selected' => $args['option_selected'] ?? null,
                'campaign' => $args['campaign'] ?? null,  // Slug de la campaña
                'campaign_id' => $args['campaign_id'] ?? null,
                'source' => $args['source'] ?? 'webhook_event',
                'intention' => $args['intention'] ?? null,
                'notes' => $args['notes'] ?? null,
                'tags' => $args['tags'] ?? [],
            ];

            // Procesar lead (crear o actualizar)
            $lead = $this->leadService->processIncomingWebhookLead($leadData);

            Log::info('Lead registrado exitosamente desde evento', [
                'lead_id' => $lead->id,
                'phone' => $lead->phone,
                'is_new' => $lead->wasRecentlyCreated,
            ]);

            return response()->json([
                'success' => true,
                'event' => $this->getEventName(),
                'message' => 'Lead registered successfully',
                'data' => new LeadResource($lead->load('campaign')),
                'is_new' => $lead->wasRecentlyCreated,
            ], $lead->wasRecentlyCreated ? 201 : 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('Error de validación en webhook_register_phone', [
                'error' => $e->getMessage(),
                'args' => $args,
            ]);

            return response()->json([
                'success' => false,
                'event' => $this->getEventName(),
                'message' => 'Validation error',
                'error' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook_register_phone', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'args' => $args,
            ]);

            return response()->json([
                'success' => false,
                'event' => $this->getEventName(),
                'message' => 'Error processing event',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function validate(array $args): array
    {
        $validator = Validator::make($args, [
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'option_selected' => ['nullable', 'string', 'in:1,2,i,t'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'string', 'exists:campaigns,id'],
            'source' => ['nullable', 'string', 'max:100'],
            'intention' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return [];
    }
}

