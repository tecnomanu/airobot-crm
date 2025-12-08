<?php

namespace App\Services\CallProvider;

use App\DTOs\CallProvider\CallEndedEventDTO;
use App\DTOs\CallProvider\CallEventDTO;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadCall;
use App\Repositories\Interfaces\LeadCallRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Services\Lead\LeadCallService;
use Illuminate\Support\Facades\Log;

/**
 * Central manager for processing call webhooks from different providers
 */
class CallProviderManager
{
    private array $providers = [];

    public function __construct(
        private LeadCallRepositoryInterface $leadCallRepository,
        private CampaignRepositoryInterface $campaignRepository,
        private LeadRepositoryInterface $leadRepository,
        private LeadCallService $leadCallService,
    ) {
        // Register available providers
        $this->registerProvider(new RetellCallProviderService);
    }

    public function registerProvider(CallProviderServiceInterface $provider): void
    {
        $this->providers[$provider->getProviderName()] = $provider;
    }

    public function getProvider(string $providerName): ?CallProviderServiceInterface
    {
        return $this->providers[$providerName] ?? null;
    }

    /**
     * Process webhook from any provider
     */
    public function processWebhook(string $providerName, array $payload, array $headers = [], string $rawBody = ''): LeadCall
    {
        $provider = $this->getProvider($providerName);

        if (! $provider) {
            throw new \InvalidArgumentException("Proveedor no soportado: {$providerName}");
        }

        if (! $provider->validateWebhookSignature($headers, $rawBody)) {
            Log::warning("Webhook signature validation failed for provider: {$providerName}");
        }

        $event = $provider->parseWebhook($payload);

        Log::info('Call webhook received', [
            'provider' => $providerName,
            'event_type' => $event->eventType,
            'call_id' => $event->callIdExternal,
        ]);

        return match ($event->eventType) {
            'call_started' => $this->handleCallStarted($event),
            'call_ended' => $this->handleCallEnded($event),
            'call_ongoing' => $this->handleCallOngoing($event),
            default => throw new \InvalidArgumentException("Tipo de evento no soportado: {$event->eventType}"),
        };
    }

    private function handleCallStarted(CallEventDTO $event): LeadCall
    {
        $existing = $this->leadCallRepository->findByRetellCallId($event->callIdExternal);

        if ($existing) {
            Log::info('Call already exists, skipping creation', ['call_id' => $event->callIdExternal]);

            return $existing;
        }

        $lead = $this->findLeadByPhone($event->getLeadPhone());

        $campaignId = $event->campaignId;
        $clientId = null;

        if ($lead) {
            $campaignId = $campaignId ?? $lead->campaign_id;
            $clientId = $lead->campaign?->client_id;
        } elseif ($campaignId) {
            $campaign = $this->campaignRepository->findById($campaignId);
            $clientId = $campaign?->client_id;
        }

        if (! $campaignId || ! $clientId) {
            throw new \InvalidArgumentException('No se pudo determinar campaign_id o client_id para la llamada');
        }

        $data = array_merge($this->mapEventToLeadCallData($event), [
            'lead_id' => $lead?->id,
            'campaign_id' => $campaignId,
        ]);

        return $this->leadCallRepository->create($data);
    }

    private function handleCallEnded(CallEventDTO $event): LeadCall
    {
        $leadCall = $this->leadCallRepository->findByRetellCallId($event->callIdExternal);

        if ($leadCall) {
            $updatedCall = $this->leadCallRepository->update($leadCall, $this->mapEventToLeadCallData($event));

            if ($leadCall->lead_id && $this->hasIntentInformation($event)) {
                $this->processIntentFromCall($leadCall, $event);
            }

            return $updatedCall;
        }

        $newCall = $this->handleCallStarted($event);

        if ($newCall->lead_id && $this->hasIntentInformation($event)) {
            $this->processIntentFromCall($newCall, $event);
        }

        return $newCall;
    }

    private function hasIntentInformation(CallEventDTO $event): bool
    {
        $metadata = $event->metadata ?? [];

        return isset($metadata['intent']) || isset($metadata['summary']) || isset($metadata['analysis']);
    }

    private function processIntentFromCall(LeadCall $leadCall, CallEventDTO $event): void
    {
        try {
            $metadata = $event->metadata ?? [];

            $intent = $metadata['intent']
                ?? $metadata['analysis']['intent']
                ?? $metadata['result']
                ?? 'not_interested';

            $callEndedDTO = CallEndedEventDTO::fromArray([
                'lead_id' => $leadCall->lead_id,
                'call_id_external' => $event->callIdExternal,
                'duration_seconds' => $event->durationSeconds ?? 0,
                'intent' => $intent,
                'summary' => $metadata['summary'] ?? $event->transcript ?? null,
                'recording_url' => $event->recordingUrl,
                'transcript' => $event->transcript,
                'metadata' => $metadata,
            ]);

            $this->leadCallService->processCallEndedEvent($callEndedDTO);

            Log::info('Intent procesado desde llamada', [
                'lead_id' => $leadCall->lead_id,
                'call_id' => $leadCall->id,
                'intent' => $intent,
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando intent desde llamada', [
                'lead_id' => $leadCall->lead_id,
                'call_id' => $leadCall->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleCallOngoing(CallEventDTO $event): LeadCall
    {
        $leadCall = $this->leadCallRepository->findByRetellCallId($event->callIdExternal);

        if ($leadCall) {
            $updates = [];
            if ($event->transcript) {
                $updates['transcript'] = $event->transcript;
            }
            if (! empty($updates)) {
                return $this->leadCallRepository->update($leadCall, $updates);
            }

            return $leadCall;
        }

        return $this->handleCallStarted($event);
    }

    private function findLeadByPhone(?string $phone): ?Lead
    {
        if (! $phone) {
            return null;
        }

        return $this->leadRepository->findByPhone($phone);
    }

    /**
     * Map CallEventDTO to LeadCall data array
     */
    private function mapEventToLeadCallData(CallEventDTO $event): array
    {
        return [
            'retell_call_id' => $event->callIdExternal,
            'duration_seconds' => $event->durationSeconds ?? ($event->durationMs ? (int) round($event->durationMs / 1000) : 0),
            'recording_url' => $event->recordingUrl,
            'transcript' => $event->transcript,
            'status' => $event->callStatus ?? 'unknown',
            'call_date' => $event->timestamp ?? now(),
            'direction' => $event->direction ?? 'outbound',
            'from_number' => $event->fromNumber,
            'to_number' => $event->toNumber,
            'disconnection_reason' => $event->disconnectionReason,
            'metadata' => $event->metadata,
        ];
    }
}
