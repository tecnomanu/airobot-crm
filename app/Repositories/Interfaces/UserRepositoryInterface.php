<?php

namespace App\Repositories\Interfaces;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get paginated users with optional filters.
     */
    public function paginate(array $filters = [], int $perPage = 15, ?User $viewer = null): LengthAwarePaginator;

    /**
     * Find user by ID with optional relations.
     */
    public function findById(int $id, array $with = []): ?User;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update an existing user.
     */
    public function update(User $user, array $data): User;

    /**
     * Delete a user.
     */
    public function delete(User $user): bool;

    /**
     * Get sellers for a specific client.
     */
    public function getSellersForClient(string $clientId): Collection;

    /**
     * Get sellers available for a campaign (client sellers + global sellers).
     */
    public function getSellersForCampaign(?string $clientId): Collection;

    /**
     * Get all sellers.
     */
    public function getAllSellers(): Collection;

    /**
     * Get users by client ID.
     */
    public function getByClientId(string $clientId): Collection;

    /**
     * Count users by role.
     */
    public function countByRole(UserRole $role): int;
}
