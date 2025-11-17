<?php

namespace App\DTOs\CallProvider;

use Carbon\Carbon;

/**
 * DTO para representar un evento de llamada de forma unificada
 * independientemente del proveedor (Retell, Vapi, etc.)
 */
class CallEventDTO
{
    public function __construct(
        public readonly string $eventType,           // call_started, call_ended, call_ongoing
        public readonly string $callIdExternal,      // ID de la llamada del proveedor
        public readonly string $provider,            // retell, vapi, etc.
        public readonly ?string $agentId = null,
        public readonly ?string $agentName = null,
        public readonly ?string $status = null,      // completed, no_answer, voicemail_reached, etc.
        public readonly ?Carbon $startedAt = null,
        public readonly ?Carbon $endedAt = null,
        public readonly ?int $durationSeconds = null,
        public readonly ?string $transcript = null,
        public readonly ?string $recordingUrl = null,
        public readonly ?float $cost = null,
        public readonly ?string $fromNumber = null,
        public readonly ?string $toNumber = null,
        public readonly ?string $direction = null,   // inbound, outbound
        public readonly ?string $disconnectionReason = null,
        public readonly ?array $metadata = null,     // datos adicionales del proveedor
        public readonly ?string $campaignId = null,  // si viene en dynamic_variables
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
    ) {}

    /**
     * Mapear a datos para crear/actualizar CallHistory
     */
    public function toCallHistoryData(): array
    {
        return array_filter([
            'call_id_external' => $this->callIdExternal,
            'provider' => $this->provider,
            'status' => $this->mapStatusToEnum(),
            'call_date' => $this->startedAt,
            'duration_seconds' => $this->durationSeconds,
            'cost' => $this->cost,
            'transcript' => $this->transcript,
            'recording_url' => $this->recordingUrl,
            'notes' => $this->disconnectionReason ? "Disconnection: {$this->disconnectionReason}" : null,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }

    /**
     * Mapear status del proveedor a nuestro enum CallStatus
     */
    private function mapStatusToEnum(): string
    {
        return match ($this->status) {
            'completed', 'agent_hangup', 'user_hangup' => 'completed',
            'no_answer' => 'no_answer',
            'voicemail_reached' => 'voicemail',
            'busy' => 'busy',
            'failed' => 'failed',
            default => 'no_answer',
        };
    }

    /**
     * Determinar si el evento es final (requiere guardar registro completo)
     */
    public function isFinalEvent(): bool
    {
        return $this->eventType === 'call_ended';
    }

    /**
     * Extraer teléfono del lead (normalizado)
     */
    public function getLeadPhone(): ?string
    {
        if (! $this->toNumber) {
            return null;
        }

        // Normalizar: quitar + y espacios
        return preg_replace('/[^0-9]/', '', $this->toNumber);
    }
}
