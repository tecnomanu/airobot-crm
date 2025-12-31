<?php

namespace App\Policies;

use App\Models\Client\Client;
use App\Models\User;
use App\Policies\Traits\HasMatrizAuthorization;

/**
 * Policy for Client authorization.
 *
 * Controls access based on company relationship:
 * - Internal users (AirRobot HQ) can manage external clients
 * - Client users can only view their own client profile
 * - Internal client cannot be modified or deleted
 */
class ClientPolicy
{
    use HasMatrizAuthorization;

    /**
     * Determine whether the user can view any clients.
     */
    public function viewAny(User $user): bool
    {
        return $this->isInternalUser($user);
    }

    /**
     * Determine whether the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        if ($this->isInternalUser($user)) {
            return true;
        }

        return $user->client_id === $client->id;
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create(User $user): bool
    {
        return $this->isInternalUser($user);
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        // Internal client can never be modified (except by direct DB)
        if ($client->isInternal()) {
            return false;
        }

        if ($this->isInternalUser($user)) {
            return true;
        }

        // Client users can update their own client profile
        return $user->client_id === $client->id;
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        // Internal client can NEVER be deleted
        if ($client->isInternal()) {
            return false;
        }

        return $this->isInternalUser($user);
    }

    /**
     * Determine whether the user can toggle the client status.
     */
    public function toggleStatus(User $user, Client $client): bool
    {
        // Internal client can never be deactivated
        if ($client->isInternal()) {
            return false;
        }

        return $this->isInternalUser($user);
    }

    /**
     * Determine whether the user can dispatch leads to this client.
     */
    public function dispatchLeads(User $user, Client $client): bool
    {
        return $this->isInternalUser($user);
    }
}
