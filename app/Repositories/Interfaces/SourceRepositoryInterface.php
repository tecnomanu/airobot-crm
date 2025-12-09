<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Integration\Source;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interfaz para el repositorio de Sources
 */
interface SourceRepositoryInterface
{
    /**
     * Obtiene todas las fuentes con paginación y filtros
     *
     * @param  array  $filters  [type, status, client_id, search]
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtiene todas las fuentes sin paginación
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Busca una fuente por ID
     */
    public function findById(int $id): ?Source;

    /**
     * Busca una fuente por ID o lanza excepción
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Source;

    /**
     * Crea una nueva fuente
     */
    public function create(array $data): Source;

    /**
     * Actualiza una fuente existente
     */
    public function update(int $id, array $data): Source;

    /**
     * Elimina una fuente
     */
    public function delete(int $id): bool;

    /**
     * Obtiene fuentes por tipo
     */
    public function findByType(SourceType|string $type): Collection;

    /**
     * Obtiene fuentes activas por tipo
     */
    public function findActiveByType(SourceType|string $type): Collection;

    /**
     * Obtiene fuentes por cliente
     */
    public function findByClient(string|int $clientId): Collection;

    /**
     * Obtiene fuentes activas por cliente
     */
    public function findActiveByClient(string|int $clientId): Collection;

    /**
     * Obtiene fuentes por estado
     */
    public function findByStatus(SourceStatus|string $status): Collection;

    /**
     * Verifica si existe una fuente con el mismo nombre para un cliente
     */
    public function existsByName(string $name, string|int|null $clientId = null, ?int $excludeId = null): bool;

    /**
     * Cuenta fuentes por tipo
     */
    public function countByType(SourceType|string $type): int;

    /**
     * Cuenta fuentes activas
     */
    public function countActive(): int;
}
