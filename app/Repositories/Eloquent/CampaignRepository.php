<?php

namespace App\Repositories\Eloquent;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CampaignRepository implements CampaignRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Campaign::with(['client', 'creator']);

        // Filtro por cliente
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Búsqueda por nombre
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(string $id, array $with = []): ?Campaign
    {
        $query = Campaign::query();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    public function create(array $data): Campaign
    {
        return Campaign::create($data);
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($data);
        return $campaign->fresh();
    }

    public function delete(Campaign $campaign): bool
    {
        return $campaign->delete();
    }

    public function getActive(): Collection
    {
        return Campaign::where('status', CampaignStatus::ACTIVE->value)
            ->with('client')
            ->get();
    }

    public function getByClient(string $clientId): Collection
    {
        return Campaign::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findBySlug(string $slug): ?Campaign
    {
        return Campaign::where('slug', $slug)
            ->where('status', CampaignStatus::ACTIVE->value)
            ->first();
    }

    public function getLeadsCount(string $campaignId): int
    {
        return Campaign::find($campaignId)?->leads()->count() ?? 0;
    }
}

