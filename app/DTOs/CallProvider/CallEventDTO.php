<?php

namespace App\DTOs\CallProvider;

use Carbon\Carbon;

/**
 * DTO for representing a call event in a unified way
 * regardless of provider (Retell, Vapi, etc.)
 */
class CallEventDTO
{
    public function __construct(
        public readonly string $eventType,           // call_started, call_ended, call_ongoing
        public readonly string $callIdExternal,      // Provider's call ID
        public readonly string $provider,            // retell, vapi, etc.
        public readonly ?string $agentId = null,
        public readonly ?string $agentName = null,
        public readonly ?string $callStatus = null,  // completed, no_answer, voicemail_reached, etc.
        public readonly ?Carbon $startedAt = null,
        public readonly ?Carbon $endedAt = null,
        public readonly ?int $durationSeconds = null,
        public readonly ?int $durationMs = null,
        public readonly ?string $transcript = null,
        public readonly ?string $recordingUrl = null,
        public readonly ?float $cost = null,
        public readonly ?string $fromNumber = null,
        public readonly ?string $toNumber = null,
        public readonly ?string $direction = null,   // inbound, outbound
        public readonly ?string $disconnectionReason = null,
        public readonly ?array $metadata = null,     // Additional provider data
        public readonly ?string $campaignId = null,  // If present in dynamic_variables
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?Carbon $timestamp = null,
    ) {}

    /**
     * Map to LeadCall data array
     */
    public function toLeadCallData(): array
    {
        return array_filter([
            'retell_call_id' => $this->callIdExternal,
            'status' => $this->mapStatusToEnum(),
            'call_date' => $this->startedAt ?? $this->timestamp ?? now(),
            'duration_seconds' => $this->durationSeconds ?? ($this->durationMs ? (int) round($this->durationMs / 1000) : 0),
            'cost' => $this->cost,
            'transcript' => $this->transcript,
            'recording_url' => $this->recordingUrl,
            'direction' => $this->direction ?? 'outbound',
            'from_number' => $this->fromNumber,
            'to_number' => $this->toNumber,
            'disconnection_reason' => $this->disconnectionReason,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }

    /**
     * @deprecated Use toLeadCallData() instead
     */
    public function toCallHistoryData(): array
    {
        return $this->toLeadCallData();
    }

    /**
     * Map provider status to CallStatus enum
     */
    private function mapStatusToEnum(): string
    {
        return match ($this->callStatus) {
            'completed', 'agent_hangup', 'user_hangup' => 'completed',
            'no_answer' => 'no_answer',
            'voicemail_reached' => 'voicemail',
            'busy' => 'busy',
            'failed' => 'failed',
            default => 'no_answer',
        };
    }

    /**
     * Determine if this is a final event (requires saving complete record)
     */
    public function isFinalEvent(): bool
    {
        return $this->eventType === 'call_ended';
    }

    /**
     * Extract lead phone (normalized)
     */
    public function getLeadPhone(): ?string
    {
        if (! $this->toNumber) {
            return null;
        }

        return preg_replace('/[^0-9]/', '', $this->toNumber);
    }
}
