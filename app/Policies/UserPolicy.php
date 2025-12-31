<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\Traits\HasMatrizAuthorization;

/**
 * Policy for User authorization.
 *
 * Controls access to user management:
 * - Admin: Full access to all users
 * - Supervisor: Can manage users in their client (or non-admins if global)
 * - User: Can only view/edit themselves
 */
class UserPolicy
{
    use HasMatrizAuthorization;

    /**
     * Determine if user can access users menu.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasElevatedPrivileges($user);
    }

    /**
     * Determine if user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view themselves
        if ($user->id === $model->id) {
            return true;
        }

        return $this->canManageUser($user, $model);
    }

    /**
     * Determine if user can create new users.
     */
    public function create(User $user): bool
    {
        return $user->role->canManageUsers();
    }

    /**
     * Determine if user can update a specific user.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update themselves (limited fields handled in controller)
        if ($user->id === $model->id) {
            return true;
        }

        return $this->canManageUser($user, $model);
    }

    /**
     * Determine if user can delete a specific user.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        return $this->canManageUser($user, $model);
    }

    /**
     * Determine if user can toggle seller status.
     */
    public function toggleSeller(User $user, User $model): bool
    {
        return $this->canManageUser($user, $model);
    }

    /**
     * Determine if user can toggle active/inactive status.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        // Cannot deactivate yourself
        if ($user->id === $model->id) {
            return false;
        }

        return $this->canManageUser($user, $model);
    }

    /**
     * Determine if user can assign a specific role.
     */
    public function assignRole(User $user, User $model, string $role): bool
    {
        // Only admins can assign admin role
        if ($role === UserRole::ADMIN->value && !$this->isAdmin($user)) {
            return false;
        }

        return $this->canManageUser($user, $model);
    }

    /**
     * Check if actor can manage target user.
     *
     * Admin: Can manage all users
     * Supervisor (global): Can manage non-admins
     * Supervisor (client): Can manage users in their client (except admins)
     */
    protected function canManageUser(User $actor, User $target): bool
    {
        if ($this->isAdmin($actor)) {
            return true;
        }

        if ($this->isSupervisor($actor)) {
            // Cannot manage admins
            if ($this->isAdmin($target)) {
                return false;
            }

            // Global supervisor can manage any non-admin
            if ($this->isMatrizUser($actor)) {
                return true;
            }

            // Client supervisor can only manage users in their client
            return $actor->client_id === $target->client_id;
        }

        return false;
    }
}

