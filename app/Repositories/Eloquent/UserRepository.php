<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    private const BASIC_RELATIONS = ['client'];

    public function paginate(array $filters = [], int $perPage = 15, ?User $viewer = null): LengthAwarePaginator
    {
        $query = User::with(self::BASIC_RELATIONS);

        if ($viewer) {
            $query->visibleTo($viewer);
        }

        // Filter by client: "global" = null client_id, UUID = specific client
        if (!empty($filters['client_id'])) {
            if ($filters['client_id'] === 'global') {
                $query->whereNull('client_id');
            } else {
                $query->where('client_id', $filters['client_id']);
            }
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by is_seller: "1" = sellers only, "0" = non-sellers only
        if (isset($filters['is_seller']) && $filters['is_seller'] !== '') {
            $query->where('is_seller', $filters['is_seller'] === '1');
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findById(int $id, array $with = []): ?User
    {
        $query = User::query();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh(self::BASIC_RELATIONS);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function getSellersForClient(string $clientId): Collection
    {
        return User::with(self::BASIC_RELATIONS)
            ->where('client_id', $clientId)
            ->where('is_seller', true)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function getSellersForCampaign(?string $clientId): Collection
    {
        $query = User::select('id', 'name', 'email', 'client_id', 'is_seller', 'role', 'status')
            ->where('is_seller', true)
            ->active();

        if ($clientId) {
            $query->where(function ($q) use ($clientId) {
                $q->where('client_id', $clientId)
                    ->orWhereNull('client_id');
            });
        }

        return $query->orderBy('name')->get();
    }

    public function getAllSellers(): Collection
    {
        return User::with(self::BASIC_RELATIONS)
            ->where('is_seller', true)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function getByClientId(string $clientId): Collection
    {
        return User::with(self::BASIC_RELATIONS)
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get();
    }

    public function countByRole(UserRole $role): int
    {
        return User::where('role', $role->value)->count();
    }
}
