<?php

namespace App\Services\Lead;

use App\DTOs\CallProvider\CallEndedEventDTO;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Models\Lead\LeadCall;
use App\Models\Lead\LeadMessage;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadCallRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Support\Facades\Log;

class LeadCallService
{
    public function __construct(
        private LeadCallRepositoryInterface $callRepository,
        private CampaignRepositoryInterface $campaignRepository,
        private LeadRepositoryInterface $leadRepository
    ) {}

    /**
     * Get paginated call history with filters
     */
    public function getCallHistory(array $filters = [], int $perPage = 15)
    {
        return $this->callRepository->paginate($filters, $perPage);
    }

    /**
     * Get paginated calls with filters for API
     */
    public function getCallsWithFilters(array $filters = [], int $perPage = 15)
    {
        return $this->callRepository->paginate($filters, $perPage);
    }

    /**
     * Get a call by ID
     */
    public function getCallById(string $id): ?LeadCall
    {
        return $this->callRepository->findById($id, ['campaign', 'lead', 'creator']);
    }

    /**
     * Register an incoming call (webhook from telephony provider)
     * Automatically links with lead if found by phone
     */
    public function registerIncomingCall(array $data): LeadCall
    {
        // Find lead by phone if lead_id not provided
        if (empty($data['lead_id']) && ! empty($data['phone'])) {
            $lead = $this->leadRepository->findByPhone($data['phone']);
            if ($lead) {
                $data['lead_id'] = $lead->id;
                $data['campaign_id'] = $data['campaign_id'] ?? $lead->campaign_id;
            }
        }

        // Validate lead exists
        if (empty($data['lead_id'])) {
            throw new \InvalidArgumentException('Se requiere lead_id para registrar la llamada');
        }

        // Set call date if not provided
        $data['call_date'] = $data['call_date'] ?? now();

        return $this->callRepository->create($data);
    }

    /**
     * Update a call (e.g., when it ends, update duration and transcript)
     */
    public function updateCall(string $id, array $data): LeadCall
    {
        $call = $this->callRepository->findById($id);

        if (! $call) {
            throw new \InvalidArgumentException('Llamada no encontrada');
        }

        return $this->callRepository->update($call, $data);
    }

    /**
     * Update call by external provider ID
     */
    public function updateCallByExternalId(string $externalId, array $data): ?LeadCall
    {
        $call = $this->callRepository->findByExternalId($externalId);

        if (! $call) {
            return null;
        }

        return $this->callRepository->update($call, $data);
    }

    /**
     * Get calls by client and date range
     */
    public function getCallsByClientAndDateRange(string $clientId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->callRepository->getByClientAndDateRange($clientId, $startDate, $endDate);
    }

    /**
     * Get calls by campaign
     */
    public function getCallsByCampaign(string $campaignId)
    {
        return $this->callRepository->getByCampaign($campaignId);
    }

    /**
     * Calculate total cost of calls by client
     */
    public function getTotalCostByClient(string $clientId): float
    {
        return $this->callRepository->getTotalCostByClient($clientId);
    }

    /**
     * Calculate total duration of calls by campaign (in seconds)
     */
    public function getTotalDurationByCampaign(string $campaignId): int
    {
        return $this->callRepository->getTotalDurationByCampaign($campaignId);
    }

    /**
     * Get call counts by status
     */
    public function getCallCountsByStatus(?string $clientId = null): array
    {
        return $this->callRepository->countByStatus($clientId);
    }

    /**
     * Process call ended event
     * Registers call history, creates message activity and updates lead intention
     */
    public function processCallEndedEvent(CallEndedEventDTO $dto): void
    {
        Log::info('Procesando evento de llamada finalizada', [
            'lead_id' => $dto->leadId,
            'call_id' => $dto->callIdExternal,
            'intent' => $dto->intent,
        ]);

        // Find lead by ID
        $lead = $this->leadRepository->findById($dto->leadId);

        if (! $lead) {
            Log::error('Lead no encontrado para procesar call_ended', [
                'lead_id' => $dto->leadId,
                'call_id' => $dto->callIdExternal,
            ]);
            throw new \InvalidArgumentException('Lead no encontrado');
        }

        // Update or create LeadCall
        $leadCall = $this->updateCallByExternalId($dto->callIdExternal, [
            'duration_seconds' => $dto->durationSeconds,
            'recording_url' => $dto->recordingUrl,
            'transcript' => $dto->transcript,
            'notes' => $dto->summary,
        ]);

        // If LeadCall doesn't exist, create a new one
        if (! $leadCall) {
            $leadCall = $this->registerIncomingCall([
                'phone' => $lead->phone,
                'lead_id' => $lead->id,
                'campaign_id' => $lead->campaign_id,
                'call_date' => now(),
                'duration_seconds' => $dto->durationSeconds,
                'retell_call_id' => $dto->callIdExternal,
                'recording_url' => $dto->recordingUrl,
                'transcript' => $dto->transcript,
                'notes' => $dto->summary,
                'status' => 'completed',
            ]);

            Log::info('LeadCall creado para evento call_ended', [
                'lead_call_id' => $leadCall->id,
                'lead_id' => $lead->id,
            ]);
        }

        // Update lead intention
        $lead->update([
            'intention' => $dto->intent,
            'intention_origin' => LeadIntentionOrigin::AGENT_IA,
            'intention_status' => LeadIntentionStatus::FINALIZED,
            'intention_decided_at' => now(),
            'notes' => $dto->summary ?: $lead->notes,
        ]);

        Log::info('Lead actualizado con intención desde agente IA', [
            'lead_id' => $lead->id,
            'intent' => $dto->intent,
            'summary' => $dto->summary,
        ]);
    }
}

