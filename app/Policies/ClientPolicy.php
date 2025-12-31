<?php

namespace App\Policies;

use App\Models\Client\Client;
use App\Models\User;
use App\Policies\Traits\HasMatrizAuthorization;

/**
 * Policy for Client authorization.
 *
 * Controls access based on company relationship:
 * - Matriz (parent company) can manage all clients
 * - Client users can only view their own client profile
 */
class ClientPolicy
{
    use HasMatrizAuthorization;

    /**
     * Determine whether the user can view any clients.
     */
    public function viewAny(User $user): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        if ($this->isMatrizUser($user)) {
            return true;
        }

        return $user->client_id === $client->id;
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create(User $user): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        if ($this->isMatrizUser($user)) {
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
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can dispatch leads to this client.
     */
    public function dispatchLeads(User $user, Client $client): bool
    {
        return $this->isMatrizUser($user);
    }
}
