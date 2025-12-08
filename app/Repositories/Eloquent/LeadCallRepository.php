<?php

namespace App\Repositories\Eloquent;

use App\Enums\CallStatus;
use App\Models\Lead\LeadCall;
use App\Repositories\Interfaces\LeadCallRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LeadCallRepository implements LeadCallRepositoryInterface
{
    private const BASIC_RELATIONS = ['campaign', 'lead'];
    private const DETAILED_RELATIONS = ['campaign', 'lead', 'creator', 'activity'];

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LeadCall::with(self::BASIC_RELATIONS);

        // Filter by client (through campaign)
        if (! empty($filters['client_id'])) {
            $query->whereHas('campaign', function ($q) use ($filters) {
                $q->where('client_id', $filters['client_id']);
            });
        }

        // Filter by campaign
        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (! empty($filters['start_date'])) {
            $query->whereDate('call_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->whereDate('call_date', '<=', $filters['end_date']);
        }

        // Search by phone
        if (! empty($filters['search'])) {
            $query->where('phone', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('call_date', 'desc')->paginate($perPage);
    }

    public function findById(string $id, array $with = []): ?LeadCall
    {
        $relations = empty($with) ? self::DETAILED_RELATIONS : $with;
        return LeadCall::with($relations)->find($id);
    }

    public function create(array $data): LeadCall
    {
        return LeadCall::create($data);
    }

    public function update(LeadCall $call, array $data): LeadCall
    {
        $call->update($data);
        return $call->fresh();
    }

    public function delete(LeadCall $call): bool
    {
        return $call->delete();
    }

    public function getByClientAndDateRange(string $clientId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = LeadCall::whereHas('campaign', function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        });

        if ($startDate) {
            $query->whereDate('call_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('call_date', '<=', $endDate);
        }

        return $query->orderBy('call_date', 'desc')->get();
    }

    public function getByCampaign(string $campaignId): Collection
    {
        return LeadCall::where('campaign_id', $campaignId)
            ->with('lead')
            ->orderBy('call_date', 'desc')
            ->get();
    }

    public function findByExternalId(string $externalId): ?LeadCall
    {
        return LeadCall::where('retell_call_id', $externalId)->first();
    }

    public function findByRetellCallId(string $retellCallId): ?LeadCall
    {
        return $this->findByExternalId($retellCallId);
    }

    public function getTotalCostByClient(string $clientId): float
    {
        return LeadCall::whereHas('campaign', function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })->sum('cost');
    }

    public function getTotalDurationByCampaign(string $campaignId): int
    {
        return LeadCall::where('campaign_id', $campaignId)->sum('duration_seconds');
    }

    public function countByStatus(?string $clientId = null): array
    {
        $query = LeadCall::query();

        if ($clientId) {
            $query->whereHas('campaign', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
        }

        $counts = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses have a value
        foreach (CallStatus::cases() as $status) {
            if (! isset($counts[$status->value])) {
                $counts[$status->value] = 0;
            }
        }

        return $counts;
    }
}
