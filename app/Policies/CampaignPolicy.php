<?php

namespace App\Policies;

use App\Models\Campaign\Campaign;
use App\Models\User;

/**
 * Policy for Campaign authorization.
 *
 * Controls access based on company relationship:
 * - Matriz (parent company) can manage all campaigns
 * - Client users can only view/manage their own campaigns
 */
class CampaignPolicy
{
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

        return $this->campaignBelongsToUserClient($user, $campaign);
    }

    /**
     * Determine whether the user can create campaigns.
     */
    public function create(User $user): bool
    {
        // Only matriz users can create campaigns
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        // Only matriz users can update campaigns
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        // Only matriz users can delete campaigns
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

    /**
     * Check if user belongs to the matriz (parent company).
     */
    private function isMatrizUser(User $user): bool
    {
        return !property_exists($user, 'client_id') || $user->client_id === null;
    }

    /**
     * Check if the campaign belongs to the user's client.
     */
    private function campaignBelongsToUserClient(User $user, Campaign $campaign): bool
    {
        if (!property_exists($user, 'client_id') || $user->client_id === null) {
            return false;
        }

        return $campaign->client_id === $user->client_id;
    }
}

