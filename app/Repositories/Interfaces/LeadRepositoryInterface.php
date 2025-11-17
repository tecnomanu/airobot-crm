<?php

namespace App\Repositories\Interfaces;

use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LeadRepositoryInterface
{
    /**
     * Obtener leads paginados con filtros opcionales
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Buscar lead por ID con relaciones
     */
    public function findById(string $id, array $with = []): ?Lead;

    /**
     * Buscar lead por teléfono
     */
    public function findByPhone(string $phone): ?Lead;

    /**
     * Crear un nuevo lead
     */
    public function create(array $data): Lead;

    /**
     * Actualizar un lead existente
     */
    public function update(Lead $lead, array $data): Lead;

    /**
     * Eliminar un lead
     */
    public function delete(Lead $lead): bool;

    /**
     * Obtener leads por campaña y estado
     */
    public function getByCampaignAndStatus(string $campaignId, ?string $status = null): Collection;

    /**
     * Obtener leads pendientes de webhook
     */
    public function getPendingWebhook(): Collection;

    /**
     * Contar leads por estado para una campaña
     */
    public function countByStatus(string $campaignId): array;

    /**
     * Obtener últimos leads creados
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Buscar lead por teléfono y campaña (campaignId opcional)
     */
    public function findByPhoneAndCampaign(string $phone, ?string $campaignId = null): ?Lead;
}
