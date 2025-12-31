<?php

namespace App\Policies\Traits;

use App\Models\User;

/**
 * Trait for common authorization logic across policies.
 *
 * Provides consistent methods for determining user access based on
 * matriz (parent company) vs client user relationships.
 */
trait HasMatrizAuthorization
{
    /**
     * Check if user belongs to matriz (parent company).
     * Matriz users have no client_id association (null).
     */
    protected function isMatrizUser(User $user): bool
    {
        return $user->client_id === null;
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
}
