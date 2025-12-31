<?php

namespace App\Policies;

use App\Models\Lead\Lead;
use App\Models\User;
use App\Policies\Traits\HasMatrizAuthorization;

/**
 * Policy for Lead authorization.
 *
 * Controls access based on company relationship:
 * - Matriz (parent company) can see distributed leads
 * - Client users can only see their own leads
 */
class LeadPolicy
{
    use HasMatrizAuthorization;

    /**
     * Determine whether the user can view any leads.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the lead.
     */
    public function view(User $user, Lead $lead): bool
    {
        if ($this->isMatrizUser($user)) {
            return true;
        }

        return $this->leadBelongsToUserClient($user, $lead);
    }

    /**
     * Determine whether the user can create leads.
     */
    public function create(User $user): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can update the lead.
     */
    public function update(User $user, Lead $lead): bool
    {
        if ($this->isMatrizUser($user)) {
            return true;
        }

        return $this->leadBelongsToUserClient($user, $lead);
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can dispatch leads to clients.
     */
    public function dispatch(User $user, Lead $lead): bool
    {
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can initiate calls on the lead.
     */
    public function initiateCall(User $user, Lead $lead): bool
    {
        if ($this->isMatrizUser($user)) {
            return true;
        }

        return $this->leadBelongsToUserClient($user, $lead);
    }

    /**
     * Determine whether the user can send WhatsApp messages to the lead.
     */
    public function sendWhatsApp(User $user, Lead $lead): bool
    {
        if ($this->isMatrizUser($user)) {
            return true;
        }

        return $this->leadBelongsToUserClient($user, $lead);
    }

    /**
     * Determine whether the user can retry automation.
     */
    public function retryAutomation(User $user, Lead $lead): bool
    {
        if ($this->isMatrizUser($user)) {
            return true;
        }

        return $this->leadBelongsToUserClient($user, $lead);
    }

    /**
     * Check if the lead belongs to the user's client.
     */
    protected function leadBelongsToUserClient(User $user, Lead $lead): bool
    {
        if ($user->client_id === null) {
            return false;
        }

        // Lead has direct client_id
        if ($lead->client_id === $user->client_id) {
            return true;
        }

        // Lead belongs to a campaign owned by user's client
        if ($lead->campaign && $lead->campaign->client_id === $user->client_id) {
            return true;
        }

        return false;
    }
}
