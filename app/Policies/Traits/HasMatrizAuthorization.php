<?php

namespace App\Policies\Traits;

use App\Enums\ClientType;
use App\Models\Client\Client;
use App\Models\User;

/**
 * Trait for common authorization logic across policies.
 *
 * Provides consistent methods for determining user access based on
 * internal (AirRobot HQ) vs external client user relationships.
 */
trait HasMatrizAuthorization
{
    /**
     * Check if user belongs to the internal AirRobot HQ client.
     * Internal users are the platform owners/administrators.
     */
    protected function isInternalUser(User $user): bool
    {
        return $user->client_id === Client::INTERNAL_CLIENT_ID;
    }

    /**
     * @deprecated Use isInternalUser() instead.
     */
    protected function isMatrizUser(User $user): bool
    {
        return $this->isInternalUser($user);
    }

    /**
     * Check if user belongs to a specific client.
     */
    protected function belongsToClient(User $user, ?string $clientId): bool
    {
        if ($user->client_id === null || $clientId === null) {
            return false;
        }

        return $user->client_id === $clientId;
    }

    /**
     * Check if user belongs to same client as a resource.
     */
    protected function belongsToSameClient(User $user, ?string $resourceClientId): bool
    {
        return $this->belongsToClient($user, $resourceClientId);
    }

    /**
     * Check if user is admin role.
     */
    protected function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Check if user is supervisor role.
     */
    protected function isSupervisor(User $user): bool
    {
        return $user->isSupervisor();
    }

    /**
     * Check if user has elevated privileges (admin or supervisor).
     */
    protected function hasElevatedPrivileges(User $user): bool
    {
        return $this->isAdmin($user) || $this->isSupervisor($user);
    }

    /**
     * Check if user is a superadmin (internal admin with full access).
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $this->isInternalUser($user) && $this->isAdmin($user);
    }
}
