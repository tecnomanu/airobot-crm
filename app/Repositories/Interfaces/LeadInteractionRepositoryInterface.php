<?php

namespace App\Repositories\Interfaces;

use App\Models\LeadInteraction;
use Illuminate\Support\Collection;

interface LeadInteractionRepositoryInterface
{
    /**
     * Crear una nueva interacción
     */
    public function create(array $data): LeadInteraction;

    /**
     * Obtener interacciones de un lead
     */
    public function getByLead(int $leadId, int $limit = 10): Collection;

    /**
     * Obtener interacciones por external_id
     */
    public function findByExternalId(string $externalId): ?LeadInteraction;

    /**
     * Obtener interacciones recientes por teléfono
     */
    public function getRecentByPhone(string $phone, int $limit = 5): Collection;

    /**
     * Obtener interacciones de una campaña
     */
    public function getByCampaign(int $campaignId): Collection;
}

