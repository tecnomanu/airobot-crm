<?php

namespace App\Services\CallProvider;

use App\DTOs\CallProvider\CallEventDTO;
use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Models\CallHistory;
use App\Models\Lead;
use App\Models\LeadInteraction;
use App\Repositories\Interfaces\CallHistoryRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Manager central para procesar webhooks de llamadas
 * de diferentes proveedores
 */
class CallProviderManager
{
    private array $providers = [];

    public function __construct(
        private CallHistoryRepositoryInterface $callHistoryRepository,
        private CampaignRepositoryInterface $campaignRepository,
        private LeadRepositoryInterface $leadRepository,
    ) {
        // Registrar proveedores disponibles
        $this->registerProvider(new RetellCallProviderService());
    }

    /**
     * Registrar un proveedor
     */
    public function registerProvider(CallProviderServiceInterface $provider): void
    {
        $this->providers[$provider->getProviderName()] = $provider;
    }

    /**
     * Obtener proveedor por nombre
     */
    public function getProvider(string $providerName): ?CallProviderServiceInterface
    {
        return $this->providers[$providerName] ?? null;
    }

    /**
     * Procesar webhook de cualquier proveedor
     */
    public function processWebhook(string $providerName, array $payload, array $headers = [], string $rawBody = ''): CallHistory
    {
        $provider = $this->getProvider($providerName);

        if (!$provider) {
            throw new \InvalidArgumentException("Proveedor no soportado: {$providerName}");
        }

        // Validar firma (si aplica)
        if (!$provider->validateWebhookSignature($headers, $rawBody)) {
            Log::warning("Webhook signature validation failed for provider: {$providerName}");
            // En producción, lanzar excepción
            // throw new \Exception('Invalid webhook signature');
        }

        // Parsear webhook a DTO
        $event = $provider->parseWebhook($payload);

        Log::info("Call webhook received", [
            'provider' => $providerName,
            'event_type' => $event->eventType,
            'call_id' => $event->callIdExternal,
        ]);

        // Procesar según tipo de evento
        return match ($event->eventType) {
            'call_started' => $this->handleCallStarted($event),
            'call_ended' => $this->handleCallEnded($event),
            'call_ongoing' => $this->handleCallOngoing($event),
            default => throw new \InvalidArgumentException("Tipo de evento no soportado: {$event->eventType}"),
        };
    }

    /**
     * Manejar evento call_started
     */
    private function handleCallStarted(CallEventDTO $event): CallHistory
    {
        // Buscar si ya existe el registro (por si llega duplicado)
        $existing = $this->callHistoryRepository->findByExternalId($event->callIdExternal);

        if ($existing) {
            Log::info("Call already exists, skipping creation", ['call_id' => $event->callIdExternal]);
            return $existing;
        }

        // Buscar lead por teléfono
        $lead = $this->findLeadByPhone($event->getLeadPhone());

        // Inferir campaign y client
        $campaignId = $event->campaignId;
        $clientId = null;

        if ($lead) {
            $campaignId = $campaignId ?? $lead->campaign_id;
            $clientId = $lead->campaign?->client_id;
        } elseif ($campaignId) {
            $campaign = $this->campaignRepository->findById($campaignId);
            $clientId = $campaign?->client_id;
        }

        if (!$campaignId || !$clientId) {
            throw new \InvalidArgumentException('No se pudo determinar campaign_id o client_id para la llamada');
        }

        // Crear registro inicial
        $data = array_merge($event->toCallHistoryData(), [
            'lead_id' => $lead?->id,
            'campaign_id' => $campaignId,
            'client_id' => $clientId,
        ]);

        return $this->callHistoryRepository->create($data);
    }

    /**
     * Manejar evento call_ended
     */
    private function handleCallEnded(CallEventDTO $event): CallHistory
    {
        // Buscar registro existente
        $callHistory = $this->callHistoryRepository->findByExternalId($event->callIdExternal);

        if ($callHistory) {
            // Actualizar con datos finales
            $updatedCall = $this->callHistoryRepository->update($callHistory, $event->toCallHistoryData());

            // Guardar interacción y actualizar intention del lead si hay transcript
            if ($event->transcript && $callHistory->lead_id) {
                $this->saveCallInteraction($updatedCall, $event);
            }

            return $updatedCall;
        }

        // Si no existe (llegó directamente call_ended sin call_started)
        // Crear registro completo
        $newCall = $this->handleCallStarted($event);

        // Guardar interacción
        if ($event->transcript && $newCall->lead_id) {
            $this->saveCallInteraction($newCall, $event);
        }

        return $newCall;
    }

    /**
     * Manejar evento call_ongoing (actualizaciones durante la llamada)
     */
    private function handleCallOngoing(CallEventDTO $event): CallHistory
    {
        $callHistory = $this->callHistoryRepository->findByExternalId($event->callIdExternal);

        if ($callHistory) {
            // Actualizar transcript parcial si existe
            $updates = [];
            if ($event->transcript) {
                $updates['transcript'] = $event->transcript;
            }
            if (!empty($updates)) {
                return $this->callHistoryRepository->update($callHistory, $updates);
            }
            return $callHistory;
        }

        // Si no existe, crear
        return $this->handleCallStarted($event);
    }

    /**
     * Buscar lead por teléfono normalizado
     */
    private function findLeadByPhone(?string $phone): ?Lead
    {
        if (!$phone) {
            return null;
        }

        return $this->leadRepository->findByPhone($phone);
    }

    /**
     * Guardar llamada como interacción y actualizar intention del lead
     */
    private function saveCallInteraction(CallHistory $callHistory, CallEventDTO $event): void
    {
        if (!$callHistory->lead) {
            return;
        }

        $lead = $callHistory->lead;

        // Preparar contenido de la interacción
        $content = $this->formatCallInteractionContent($event);

        // Guardar interacción
        LeadInteraction::create([
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'channel' => InteractionChannel::CALL,
            'direction' => $event->direction === 'outbound' ? InteractionDirection::OUTBOUND : InteractionDirection::INBOUND,
            'content' => $content,
            'payload' => [
                'call_id' => $callHistory->id,
                'call_id_external' => $event->callIdExternal,
                'duration_ms' => $event->durationMs,
                'disconnection_reason' => $event->disconnectionReason,
                'call_status' => $event->callStatus,
            ],
            'external_id' => $event->callIdExternal,
            'phone' => $lead->phone,
        ]);

        // Actualizar intention del lead con el transcript
        $this->updateLeadIntentionFromCall($lead, $event);

        Log::info('Interacción de llamada guardada', [
            'lead_id' => $lead->id,
            'call_id' => $callHistory->id,
        ]);
    }

    /**
     * Formatear contenido de la interacción desde la llamada
     */
    private function formatCallInteractionContent(CallEventDTO $event): string
    {
        $parts = [];

        $parts[] = "📞 Llamada finalizada";
        $parts[] = "Estado: " . ($event->callStatus ?? 'desconocido');

        if ($event->durationMs) {
            $seconds = round($event->durationMs / 1000);
            $parts[] = "Duración: {$seconds}s";
        }

        if ($event->disconnectionReason) {
            $parts[] = "Razón: {$event->disconnectionReason}";
        }

        if ($event->transcript) {
            $parts[] = "\n--- Transcripción ---\n{$event->transcript}";
        }

        return implode("\n", $parts);
    }

    /**
     * Actualizar intention del lead con información de la llamada
     */
    private function updateLeadIntentionFromCall(Lead $lead, CallEventDTO $event): void
    {
        if (!$event->transcript) {
            return;
        }

        // Concatenar con la intención anterior si existe
        $previousIntention = $lead->intention ?? '';

        $callSummary = "[" . now()->format('Y-m-d H:i') . "] Llamada";

        if ($event->disconnectionReason === 'voicemail_reached') {
            $callSummary .= " (buzón de voz)";
        } elseif ($event->disconnectionReason === 'agent_hangup') {
            $callSummary .= " (completada)";
        }

        $callSummary .= ":\n" . $event->transcript;

        $newIntention = $previousIntention
            ? $previousIntention . "\n\n" . $callSummary
            : $callSummary;

        // Limitar longitud para no llenar demasiado la DB
        if (strlen($newIntention) > 5000) {
            // Mantener solo los últimos 5000 caracteres
            $newIntention = '...' . substr($newIntention, -4997);
        }

        $lead->update([
            'intention' => $newIntention,
        ]);

        Log::info('Intención del lead actualizada desde llamada', [
            'lead_id' => $lead->id,
            'transcript_length' => strlen($event->transcript ?? ''),
        ]);
    }
}
