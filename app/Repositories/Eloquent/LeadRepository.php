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
        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        // Filtro por estado
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por source (puede ser string único o CSV de múltiples)
        if (! empty($filters['source'])) {
            $sources = is_array($filters['source'])
                ? $filters['source']
                : explode(',', $filters['source']);

            $query->whereIn('source', $sources);
        }

        // Búsqueda por texto (nombre o teléfono)
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        // Filtro por existencia de interacciones
        if (! empty($filters['has_interactions'])) {
            $query->has('interactions');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(string $id, array $with = []): ?Lead
    {
        $query = Lead::query();

        if (! empty($with)) {
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
            if (! isset($counts[$status->value])) {
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

    public function findByPhoneWithVariants(string $phone): ?Lead
    {
        // Buscar con el formato exacto
        $lead = Lead::where('phone', $phone)->first();

        if ($lead) {
            return $lead;
        }

        // Limpiar número para búsqueda flexible
        $cleanPhone = str_replace(['+', ' ', '-'], '', $phone);

        // Buscar variantes comunes
        return Lead::where(function ($query) use ($cleanPhone, $phone) {
            $query->where('phone', $phone)
                ->orWhere('phone', '+' . $cleanPhone)
                ->orWhere('phone', $cleanPhone);

            // Si empieza con 549, también buscar sin el 9 (Argentina)
            if (str_starts_with($cleanPhone, '549')) {
                $withoutNine = '54' . substr($cleanPhone, 3);
                $query->orWhere('phone', '+' . $withoutNine)
                    ->orWhere('phone', $withoutNine);
            }

            // Si empieza con 54 (sin 9), también buscar con el 9
            if (str_starts_with($cleanPhone, '54') && ! str_starts_with($cleanPhone, '549')) {
                $withNine = '549' . substr($cleanPhone, 2);
                $query->orWhere('phone', '+' . $withNine)
                    ->orWhere('phone', $withNine);
            }
        })->first();
    }

    public function getFailedAutomation(array $filters = []): Collection
    {
        $query = Lead::whereIn('automation_status', ['failed', 'pending'])
            ->orWhereNotNull('automation_error');

        // Aplicar filtros adicionales
        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (! empty($filters['option_selected'])) {
            $query->where('option_selected', $filters['option_selected']);
        }

        return $query->with(['campaign'])->get();
    }

    public function getPendingAutomation(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Lead::with(['campaign', 'creator'])
            ->whereIn('automation_status', ['failed', 'pending'])
            ->orWhereNotNull('automation_error');

        // Aplicar filtros adicionales
        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }
}
