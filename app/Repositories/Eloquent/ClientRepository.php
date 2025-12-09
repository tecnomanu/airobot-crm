<?php

namespace App\Repositories\Eloquent;

use App\Enums\ClientStatus;
use App\Models\Client\Client;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ClientRepository implements ClientRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::with('creator');

        // Filtro por estado
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Búsqueda por nombre o empresa
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('company', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(string $id, array $with = []): ?Client
    {
        $query = Client::query();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client->fresh();
    }

    public function delete(Client $client): bool
    {
        return $client->delete();
    }

    public function getActive(): Collection
    {
        return Client::where('status', ClientStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();
    }

    public function findByEmail(string $email): ?Client
    {
        return Client::where('email', $email)->first();
    }

    public function getMetrics(string $clientId): array
    {
        $client = Client::with(['campaigns', 'calls'])->find($clientId);

        if (! $client) {
            return [];
        }

        $totalLeads = $client->campaigns->sum(function ($campaign) {
            return $campaign->leads()->count();
        });

        return [
            'total_campaigns' => $client->campaigns->count(),
            'active_campaigns' => $client->campaigns->where('status', 'active')->count(),
            'total_leads' => $totalLeads,
            'total_calls' => $client->calls->count(),
            'total_cost' => $client->calls->sum('cost'),
            'completed_calls' => $client->calls->where('status', 'completed')->count(),
        ];
    }
}
