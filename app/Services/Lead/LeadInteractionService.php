<?php

namespace App\Services\Lead;

use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Helpers\PhoneHelper;
use App\Models\LeadInteraction;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadInteractionRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadInteractionService
{
    public function __construct(
        private LeadInteractionRepositoryInterface $interactionRepository,
        private LeadRepositoryInterface $leadRepository,
        private CampaignRepositoryInterface $campaignRepository
    ) {}

    /**
     * Registrar una interacción entrante de WhatsApp
     */
    public function registerWhatsappInbound(array $data): LeadInteraction
    {
        return DB::transaction(function () use ($data) {
            // Normalizar teléfono
            $phone = PhoneHelper::normalize($data['phone']);

            // Evitar duplicados por external_id
            if (! empty($data['external_id'])) {
                $existing = $this->interactionRepository->findByExternalId($data['external_id']);
                if ($existing) {
                    Log::info('Interacción duplicada, ignorando', ['external_id' => $data['external_id']]);

                    return $existing;
                }
            }

            // Buscar lead por teléfono
            $lead = $this->leadRepository->findByPhone($phone);
            $campaign = null;

            if ($lead) {
                $campaign = $lead->campaign;
            } elseif (! empty($data['campaign_id'])) {
                $campaign = $this->campaignRepository->findById($data['campaign_id']);
            }

            $interactionData = [
                'lead_id' => $lead?->id,
                'campaign_id' => $campaign?->id,
                'channel' => InteractionChannel::WHATSAPP,
                'direction' => InteractionDirection::INBOUND,
                'content' => $data['content'],
                'payload' => $data['payload'] ?? null,
                'external_id' => $data['external_id'] ?? null,
                'phone' => $phone,
            ];

            $interaction = $this->interactionRepository->create($interactionData);

            // Si encontramos el lead, actualizar su estado si está pending
            if ($lead && $lead->status->value === 'pending') {
                $this->leadRepository->update($lead, [
                    'status' => 'in_progress',
                ]);
            }

            Log::info('Interacción de WhatsApp registrada', [
                'interaction_id' => $interaction->id,
                'lead_id' => $lead?->id,
                'phone' => $phone,
            ]);

            return $interaction;
        });
    }

    /**
     * Obtener interacciones de un lead
     */
    public function getLeadInteractions(int $leadId, int $limit = 10)
    {
        return $this->interactionRepository->getByLead($leadId, $limit);
    }

    /**
     * Registrar interacción saliente (ej: WhatsApp enviado por automatización)
     */
    public function registerOutbound(array $data): LeadInteraction
    {
        $interactionData = [
            'lead_id' => $data['lead_id'],
            'campaign_id' => $data['campaign_id'] ?? null,
            'channel' => $data['channel'] ?? InteractionChannel::WHATSAPP,
            'direction' => InteractionDirection::OUTBOUND,
            'content' => $data['content'],
            'payload' => $data['payload'] ?? null,
            'external_id' => $data['external_id'] ?? null,
            'phone' => $data['phone'] ?? null,
        ];

        return $this->interactionRepository->create($interactionData);
    }
}
