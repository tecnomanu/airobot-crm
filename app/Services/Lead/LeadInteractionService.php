<?php

namespace App\Services\Lead;

use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Models\LeadInteraction;
use App\Repositories\Interfaces\LeadInteractionRepositoryInterface;
use Illuminate\Support\Facades\Log;

class LeadInteractionService
{
    public function __construct(
        private LeadInteractionRepositoryInterface $interactionRepository
    ) {}

    /**
     * Obtener interacciones de un lead
     */
    public function getLeadInteractions(string $leadId, int $limit = 10)
    {
        return $this->interactionRepository->getByLead($leadId, $limit);
    }

    /**
     * Crear interacción de WhatsApp (inbound/outbound)
     * Método principal para crear interacciones de WhatsApp
     */
    public function createFromWhatsAppMessage(
        string $leadId,
        string $campaignId,
        string $content,
        array $payload,
        ?string $externalId,
        string $phone,
        InteractionDirection $direction = InteractionDirection::INBOUND
    ): LeadInteraction {
        // Evitar duplicados por external_id
        if ($externalId) {
            $existing = $this->interactionRepository->findByExternalId($externalId);
            if ($existing) {
                Log::info('Interacción duplicada detectada', [
                    'external_id' => $externalId,
                    'interaction_id' => $existing->id,
                ]);

                return $existing;
            }
        }

        return $this->interactionRepository->create([
            'lead_id' => $leadId,
            'campaign_id' => $campaignId,
            'channel' => InteractionChannel::WHATSAPP,
            'direction' => $direction,
            'content' => $content,
            'payload' => $payload,
            'external_id' => $externalId,
            'phone' => $phone,
        ]);
    }

    /**
     * Crear interacción genérica (para otros canales)
     */
    public function create(array $data): LeadInteraction
    {
        return $this->interactionRepository->create([
            'lead_id' => $data['lead_id'],
            'campaign_id' => $data['campaign_id'] ?? null,
            'channel' => $data['channel'] ?? InteractionChannel::WHATSAPP,
            'direction' => $data['direction'] ?? InteractionDirection::INBOUND,
            'content' => $data['content'],
            'payload' => $data['payload'] ?? null,
            'external_id' => $data['external_id'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
    }
}
