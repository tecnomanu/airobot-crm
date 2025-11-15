<?php

namespace App\Repositories\Eloquent;

use App\Enums\CallStatus;
use App\Models\CallHistory;
use App\Repositories\Interfaces\CallHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CallHistoryRepository implements CallHistoryRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CallHistory::with(['campaign', 'client', 'lead']);

        // Filtro por cliente
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Filtro por campaña
        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por rango de fechas
        if (!empty($filters['start_date'])) {
            $query->whereDate('call_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('call_date', '<=', $filters['end_date']);
        }

        // Búsqueda por teléfono
        if (!empty($filters['search'])) {
            $query->where('phone', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('call_date', 'desc')->paginate($perPage);
    }

    public function findById(string $id, array $with = []): ?CallHistory
    {
        $query = CallHistory::query();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    public function create(array $data): CallHistory
    {
        return CallHistory::create($data);
    }

    public function update(CallHistory $callHistory, array $data): CallHistory
    {
        $callHistory->update($data);
        return $callHistory->fresh();
    }

    public function delete(CallHistory $callHistory): bool
    {
        return $callHistory->delete();
    }

    public function getByClientAndDateRange(string $clientId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = CallHistory::where('client_id', $clientId);

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
        return CallHistory::where('campaign_id', $campaignId)
            ->with('lead')
            ->orderBy('call_date', 'desc')
            ->get();
    }

    public function findByExternalId(string $externalId): ?CallHistory
    {
        return CallHistory::where('call_id_external', $externalId)->first();
    }

    public function getTotalCostByClient(string $clientId): float
    {
        return CallHistory::where('client_id', $clientId)->sum('cost');
    }

    public function getTotalDurationByCampaign(string $campaignId): int
    {
        return CallHistory::where('campaign_id', $campaignId)->sum('duration_seconds');
    }

    public function countByStatus(?string $clientId = null): array
    {
        $query = CallHistory::query();

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $counts = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Asegurar que todos los estados tengan un valor
        foreach (CallStatus::cases() as $status) {
            if (!isset($counts[$status->value])) {
                $counts[$status->value] = 0;
            }
        }

        return $counts;
    }
}

