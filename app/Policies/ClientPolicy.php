<?php

namespace App\Policies;

use App\Models\Client\Client;
use App\Models\User;

/**
 * Policy for Client authorization.
 *
 * Controls access based on company relationship:
 * - Matriz (parent company) can manage all clients
 * - Client users can only view their own client profile
 */
class ClientPolicy
{
    /**
     * Determine whether the user can view any clients.
     */
    public function viewAny(User $user): bool
    {
        // Only matriz users can list all clients
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

        return $this->clientBelongsToUser($user, $client);
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
        return $this->clientBelongsToUser($user, $client);
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

    /**
     * Check if user belongs to the matriz (parent company).
     */
    private function isMatrizUser(User $user): bool
    {
        return !property_exists($user, 'client_id') || $user->client_id === null;
    }

    /**
     * Check if the client belongs to the user.
     */
    private function clientBelongsToUser(User $user, Client $client): bool
    {
        if (!property_exists($user, 'client_id') || $user->client_id === null) {
            return false;
        }

        return $client->id === $user->client_id;
    }
}

