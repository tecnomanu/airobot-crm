<?php

namespace App\Policies;

use App\Models\Campaign\Campaign;
use App\Models\User;
use App\Policies\Traits\HasMatrizAuthorization;

/**
 * Policy for Campaign authorization.
 *
 * Controls access based on company relationship:
 * - Matriz (parent company) can manage all campaigns
 * - Client users can only view/manage their own campaigns
 */
class CampaignPolicy
{
    use HasMatrizAuthorization;

    /**
     * Determine whether the user can view any campaigns.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the campaign.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        if ($this->isMatrizUser($user)) {
            return true;
        }

        return $this->belongsToClient($user, $campaign->client_id);
    }

    /**
     * Determine whether the user can create campaigns.
     */
    public function create(User $user): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can toggle campaign status.
     */
    public function toggleStatus(User $user, Campaign $campaign): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can manage campaign templates.
     */
    public function manageTemplates(User $user, Campaign $campaign): bool
    {
        return $this->isMatrizUser($user);
    }
}
