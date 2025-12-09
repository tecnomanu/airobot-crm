<?php

namespace App\Repositories\Interfaces;

use App\Models\Campaign\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CampaignRepositoryInterface
{
    /**
     * Obtener campañas paginadas con filtros opcionales
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Buscar campaña por ID con relaciones
     */
    public function findById(string $id, array $with = []): ?Campaign;

    /**
     * Crear una nueva campaña
     */
    public function create(array $data): Campaign;

    /**
     * Actualizar una campaña existente
     */
    public function update(Campaign $campaign, array $data): Campaign;

    /**
     * Eliminar una campaña
     */
    public function delete(Campaign $campaign): bool;

    /**
     * Obtener campañas activas
     */
    public function getActive(): Collection;

    /**
     * Obtener campañas por cliente
     */
    public function getByClient(string $clientId): Collection;

    /**
     * Buscar campaña por slug
     */
    public function findBySlug(string $slug): ?Campaign;

    /**
     * Contar leads por campaña
     */
    public function getLeadsCount(string $campaignId): int;
}
