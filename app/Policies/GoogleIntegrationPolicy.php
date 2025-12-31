<?php

namespace App\Policies;

use App\Models\Integration\GoogleIntegration;
use App\Models\User;
use App\Policies\Traits\HasMatrizAuthorization;

class GoogleIntegrationPolicy
{
    use HasMatrizAuthorization;

    /**
     * Determine whether the user can view any integrations.
     * Users can see integrations list for their own client only.
     */
    public function viewAny(User $user): bool
    {
        return $user->client_id !== null;
    }

    /**
     * Determine whether the user can view the integration.
     * Users can only view integrations belonging to their client.
     */
    public function view(User $user, GoogleIntegration $integration): bool
    {
        return $this->belongsToSameClient($user, $integration->client_id);
    }

    /**
     * Determine whether the user can create integrations.
     * Any authenticated user with a client can connect Google.
     */
    public function create(User $user): bool
    {
        return $user->client_id !== null;
    }

    /**
     * Determine whether the user can update the integration.
     * Only the creator or admin of the same client can update.
     */
    public function update(User $user, GoogleIntegration $integration): bool
    {
        if (! $this->belongsToSameClient($user, $integration->client_id)) {
            return false;
        }

        // Creator can always update their integration
        if ($integration->created_by_user_id === $user->id) {
            return true;
        }

        // Admins/supervisors of same client can update
        return $this->hasElevatedPrivileges($user);
    }

    /**
     * Determine whether the user can delete (disconnect) the integration.
     * Same rules as update.
     */
    public function delete(User $user, GoogleIntegration $integration): bool
    {
        return $this->update($user, $integration);
    }

    /**
     * Determine whether the user can use this integration for exports.
     * All users of the same client can use the integration.
     */
    public function use(User $user, GoogleIntegration $integration): bool
    {
        return $this->belongsToSameClient($user, $integration->client_id);
    }
}

