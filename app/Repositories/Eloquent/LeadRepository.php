<?php

namespace App\Repositories\Eloquent;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LeadRepository implements LeadRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Lead::with(['campaign.client', 'creator']);

        // Filtro por campaña
        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por source
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        // Búsqueda por texto (nombre o teléfono)
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(string $id, array $with = []): ?Lead
    {
        $query = Lead::query();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    public function findByPhone(string $phone): ?Lead
    {
        return Lead::where('phone', $phone)->first();
    }

    public function create(array $data): Lead
    {
        return Lead::create($data);
    }

    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);
        return $lead->fresh();
    }

    public function delete(Lead $lead): bool
    {
        return $lead->delete();
    }

    public function getByCampaignAndStatus(string $campaignId, ?string $status = null): Collection
    {
        $query = Lead::where('campaign_id', $campaignId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with('campaign')->get();
    }

    public function getPendingWebhook(): Collection
    {
        return Lead::where('webhook_sent', false)
            ->where('status', '!=', LeadStatus::INVALID->value)
            ->with(['campaign.client'])
            ->get();
    }

    public function countByStatus(string $campaignId): array
    {
        $counts = Lead::where('campaign_id', $campaignId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Asegurar que todos los estados tengan un valor
        foreach (LeadStatus::cases() as $status) {
            if (!isset($counts[$status->value])) {
                $counts[$status->value] = 0;
            }
        }

        return $counts;
    }

    public function getRecent(int $limit = 10): Collection
    {
        return Lead::with(['campaign', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function findByPhoneAndCampaign(string $phone, ?string $campaignId = null): ?Lead
    {
        $query = Lead::where('phone', $phone);
        
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }
        
        return $query->first();
    }
}

