<?php

namespace App\Repositories\Interfaces;

use App\Models\CallHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CallHistoryRepositoryInterface
{
    /**
     * Obtener historial paginado con filtros opcionales
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Buscar llamada por ID con relaciones
     */
    public function findById(string $id, array $with = []): ?CallHistory;

    /**
     * Crear un nuevo registro de llamada
     */
    public function create(array $data): CallHistory;

    /**
     * Actualizar un registro de llamada
     */
    public function update(CallHistory $callHistory, array $data): CallHistory;

    /**
     * Eliminar un registro de llamada
     */
    public function delete(CallHistory $callHistory): bool;

    /**
     * Obtener llamadas por cliente y rango de fechas
     */
    public function getByClientAndDateRange(string $clientId, ?string $startDate = null, ?string $endDate = null): Collection;

    /**
     * Obtener llamadas por campaña
     */
    public function getByCampaign(string $campaignId): Collection;

    /**
     * Buscar llamada por ID externo del proveedor
     */
    public function findByExternalId(string $externalId): ?CallHistory;

    /**
     * Calcular costo total por cliente
     */
    public function getTotalCostByClient(string $clientId): float;

    /**
     * Calcular duración total por campaña
     */
    public function getTotalDurationByCampaign(string $campaignId): int;

    /**
     * Contar llamadas por estado
     */
    public function countByStatus(?string $clientId = null): array;
}
