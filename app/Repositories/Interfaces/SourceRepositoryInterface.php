<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Source;
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
     * @param array $filters [type, status, client_id, search]
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtiene todas las fuentes sin paginación
     * 
     * @param array $filters
     * @return Collection
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Busca una fuente por ID
     * 
     * @param int $id
     * @return Source|null
     */
    public function findById(int $id): ?Source;

    /**
     * Busca una fuente por ID o lanza excepción
     * 
     * @param int $id
     * @return Source
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Source;

    /**
     * Crea una nueva fuente
     * 
     * @param array $data
     * @return Source
     */
    public function create(array $data): Source;

    /**
     * Actualiza una fuente existente
     * 
     * @param int $id
     * @param array $data
     * @return Source
     */
    public function update(int $id, array $data): Source;

    /**
     * Elimina una fuente
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Obtiene fuentes por tipo
     * 
     * @param SourceType|string $type
     * @return Collection
     */
    public function findByType(SourceType|string $type): Collection;

    /**
     * Obtiene fuentes activas por tipo
     * 
     * @param SourceType|string $type
     * @return Collection
     */
    public function findActiveByType(SourceType|string $type): Collection;

    /**
     * Obtiene fuentes por cliente
     * 
     * @param string|int $clientId
     * @return Collection
     */
    public function findByClient(string|int $clientId): Collection;

    /**
     * Obtiene fuentes activas por cliente
     * 
     * @param string|int $clientId
     * @return Collection
     */
    public function findActiveByClient(string|int $clientId): Collection;

    /**
     * Obtiene fuentes por estado
     * 
     * @param SourceStatus|string $status
     * @return Collection
     */
    public function findByStatus(SourceStatus|string $status): Collection;

    /**
     * Verifica si existe una fuente con el mismo nombre para un cliente
     * 
     * @param string $name
     * @param string|int|null $clientId
     * @param int|null $excludeId
     * @return bool
     */
    public function existsByName(string $name, string|int|null $clientId = null, ?int $excludeId = null): bool;

    /**
     * Cuenta fuentes por tipo
     * 
     * @param SourceType|string $type
     * @return int
     */
    public function countByType(SourceType|string $type): int;

    /**
     * Cuenta fuentes activas
     * 
     * @return int
     */
    public function countActive(): int;
}

