<?php

namespace App\DTOs\CallProvider;

class CallEndedEventDTO
{
    public function __construct(
        public readonly string $leadId,
        public readonly string $callIdExternal,
        public readonly int $durationSeconds,
        public readonly string $intent, // 'interested' | 'not_interested' | 'no_response'
        public readonly ?string $summary = null,
        public readonly ?string $recordingUrl = null,
        public readonly ?string $transcript = null,
        public readonly ?array $metadata = null
    ) {}

    /**
     * Crear desde array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            leadId: $data['lead_id'] ?? $data['leadId'] ?? '',
            callIdExternal: $data['call_id_external'] ?? $data['callId'] ?? $data['call_id'] ?? '',
            durationSeconds: (int) ($data['duration_seconds'] ?? $data['duration'] ?? 0),
            intent: $data['intent'] ?? 'not_interested',
            summary: $data['summary'] ?? $data['notes'] ?? null,
            recordingUrl: $data['recording_url'] ?? $data['recordingUrl'] ?? null,
            transcript: $data['transcript'] ?? null,
            metadata: $data['metadata'] ?? $data['meta'] ?? null
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'lead_id' => $this->leadId,
            'call_id_external' => $this->callIdExternal,
            'duration_seconds' => $this->durationSeconds,
            'intent' => $this->intent,
            'summary' => $this->summary,
            'recording_url' => $this->recordingUrl,
            'transcript' => $this->transcript,
            'metadata' => $this->metadata,
        ];
    }
}
