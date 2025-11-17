<?php

namespace App\Services\CallProvider;

use App\DTOs\CallProvider\CallEventDTO;
use Carbon\Carbon;

/**
 * Servicio para procesar webhooks de Retell AI
 */
class RetellCallProviderService implements CallProviderServiceInterface
{
    public function parseWebhook(array $payload): CallEventDTO
    {
        $event = $payload['event'] ?? 'call_ongoing';
        $call = $payload['call'] ?? [];

        // Extraer variables dinámicas (campaign_id, lead info, etc.)
        $dynamicVars = $call['retell_llm_dynamic_variables'] ?? [];

        // Calcular costo total
        $cost = null;
        if (isset($call['call_cost']['combined_cost'])) {
            $cost = (float) $call['call_cost']['combined_cost'];
        }

        // Calcular duración
        $durationSeconds = null;
        if (isset($call['duration_ms'])) {
            $durationSeconds = (int) ceil($call['duration_ms'] / 1000);
        } elseif (isset($call['call_cost']['total_duration_seconds'])) {
            $durationSeconds = (int) $call['call_cost']['total_duration_seconds'];
        }

        // Timestamps
        $startedAt = isset($call['start_timestamp'])
            ? Carbon::createFromTimestampMs($call['start_timestamp'])
            : null;

        $endedAt = isset($call['end_timestamp'])
            ? Carbon::createFromTimestampMs($call['end_timestamp'])
            : null;

        // Mapear status
        $status = $this->mapRetellStatus($call);

        return new CallEventDTO(
            eventType: $event,
            callIdExternal: $call['call_id'] ?? 'unknown',
            provider: 'retell',
            agentId: $call['agent_id'] ?? null,
            agentName: $call['agent_name'] ?? null,
            status: $status,
            startedAt: $startedAt,
            endedAt: $endedAt,
            durationSeconds: $durationSeconds,
            transcript: $call['transcript'] ?? null,
            recordingUrl: $call['recording_url'] ?? null,
            cost: $cost,
            fromNumber: $call['from_number'] ?? null,
            toNumber: $call['to_number'] ?? null,
            direction: $call['direction'] ?? 'outbound',
            disconnectionReason: $call['disconnection_reason'] ?? null,
            metadata: [
                'call_status' => $call['call_status'] ?? null,
                'agent_version' => $call['agent_version'] ?? null,
                'latency' => $call['latency'] ?? null,
                'llm_token_usage' => $call['llm_token_usage'] ?? null,
                'public_log_url' => $call['public_log_url'] ?? null,
            ],
            campaignId: $dynamicVars['campaign_id'] ?? null,
            firstName: $dynamicVars['first_name'] ?? null,
            lastName: $dynamicVars['last_name'] ?? null,
        );
    }

    public function validateWebhookSignature(array $headers, string $rawBody): bool
    {
        // Retell envía firma en header x-retell-signature
        // Formato: "v=timestamp,d=hash"
        $signature = $headers['x-retell-signature'] ?? $headers['X-Retell-Signature'] ?? null;

        if (! $signature) {
            return false;
        }

        // TODO: Implementar validación real con secret de Retell
        // Por ahora, solo verificamos que exista la firma
        $secret = config('services.retell.webhook_secret', env('RETELL_WEBHOOK_SECRET'));

        if (! $secret) {
            // Si no hay secret configurado, aceptar (modo desarrollo)
            return true;
        }

        // Parsear firma: v=timestamp,d=hash
        if (! preg_match('/v=(\d+),d=([a-f0-9]+)/', $signature, $matches)) {
            return false;
        }

        $timestamp = $matches[1];
        $receivedHash = $matches[2];

        // Construir payload firmado: timestamp.body
        $signedPayload = $timestamp.'.'.$rawBody;

        // Calcular hash esperado
        $expectedHash = hash_hmac('sha256', $signedPayload, $secret);

        // Comparación segura
        return hash_equals($expectedHash, $receivedHash);
    }

    public function getProviderName(): string
    {
        return 'retell';
    }

    /**
     * Mapear status de Retell a nuestro formato interno
     */
    private function mapRetellStatus(array $call): string
    {
        $callStatus = $call['call_status'] ?? 'unknown';
        $disconnectionReason = $call['disconnection_reason'] ?? null;

        // Si está en progreso, retornar ongoing
        if ($callStatus === 'ongoing') {
            return 'ongoing';
        }

        // Si terminó, usar disconnection_reason para determinar status
        if ($callStatus === 'ended') {
            return match ($disconnectionReason) {
                'agent_hangup', 'user_hangup' => 'completed',
                'voicemail_reached' => 'voicemail_reached',
                'no_answer' => 'no_answer',
                'busy' => 'busy',
                'error', 'failed' => 'failed',
                default => 'completed',
            };
        }

        return $callStatus;
    }
}
