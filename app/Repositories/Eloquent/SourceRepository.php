<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Integration\Source;
use App\Repositories\Interfaces\SourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implementación Eloquent del repositorio de Sources
 */
class SourceRepository implements SourceRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct(
        protected Source $model
    ) {}

    /**
     * {@inheritDoc}
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['client', 'creator']);

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(array $filters = []): Collection
    {
        $query = $this->model->with(['client', 'creator']);

        $this->applyFilters($query, $filters);

        return $query->latest()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Source
    {
        return $this->model
            ->with(['client', 'creator'])
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Source
    {
        return $this->model
            ->with(['client', 'creator'])
            ->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Source
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): Source
    {
        $source = $this->findOrFail($id);
        $source->update($data);

        return $source->fresh(['client', 'creator']);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $source = $this->findOrFail($id);

        return $source->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function findByType(SourceType|string $type): Collection
    {
        return $this->model
            ->with(['client', 'creator'])
            ->ofType($type)
            ->latest()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findActiveByType(SourceType|string $type): Collection
    {
        return $this->model
            ->with(['client', 'creator'])
            ->ofType($type)
            ->active()
            ->latest()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByClient(string|int $clientId): Collection
    {
        return $this->model
            ->with(['client', 'creator'])
            ->forClient($clientId)
            ->latest()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findActiveByClient(string|int $clientId): Collection
    {
        return $this->model
            ->with(['client', 'creator'])
            ->forClient($clientId)
            ->active()
            ->latest()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByStatus(SourceStatus|string $status): Collection
    {
        $statusValue = $status instanceof SourceStatus ? $status->value : $status;

        return $this->model
            ->with(['client', 'creator'])
            ->where('status', $statusValue)
            ->latest()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByName(string $name, string|int|null $clientId = null, ?int $excludeId = null): bool
    {
        $query = $this->model->where('name', $name);

        if ($clientId !== null) {
            $query->where('client_id', $clientId);
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function countByType(SourceType|string $type): int
    {
        return $this->model->ofType($type)->count();
    }

    /**
     * {@inheritDoc}
     */
    public function countActive(): int
    {
        return $this->model->active()->count();
    }

    /**
     * Aplica filtros a un query builder
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['status'])) {
            $statusValue = $filters['status'] instanceof SourceStatus
                ? $filters['status']->value
                : $filters['status'];
            $query->where('status', $statusValue);
        }

        if (isset($filters['client_id'])) {
            $query->forClient($filters['client_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if (isset($filters['active_only']) && $filters['active_only']) {
            $query->active();
        }
    }
}
