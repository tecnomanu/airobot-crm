<?php

namespace App\Policies;

use App\Models\Lead\Lead;
use App\Models\User;

/**
 * Policy for Lead authorization.
 *
 * Controls access based on company relationship:
 * - Matriz (parent company) can see distributed leads
 * - Client users can only see their own leads
 */
class LeadPolicy
{
    /**
     * Determine whether the user can view any leads.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can list leads (filtered by scope)
        return true;
    }

    /**
     * Determine whether the user can view the lead.
     */
    public function view(User $user, Lead $lead): bool
    {
        // If user belongs to matriz (no client_id), can view all
        if ($this->isMatrizUser($user)) {
            return true;
        }

        // Client users can only view leads belonging to their client
        return $this->leadBelongsToUserClient($user, $lead);
    }

    /**
     * Determine whether the user can create leads.
     */
    public function create(User $user): bool
    {
        // Only matriz users can create leads
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
        // Only matriz users can delete leads
        return $this->isMatrizUser($user);
    }

    /**
     * Determine whether the user can dispatch leads to clients.
     */
    public function dispatch(User $user, Lead $lead): bool
    {
        // Only matriz users can dispatch leads
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
     * Check if user belongs to the matriz (parent company).
     * Matriz users have no client_id association.
     */
    private function isMatrizUser(User $user): bool
    {
        // TODO: Implement client_id relationship on User model
        // For now, all users are treated as matriz users
        return !property_exists($user, 'client_id') || $user->client_id === null;
    }

    /**
     * Check if the lead belongs to the user's client.
     */
    private function leadBelongsToUserClient(User $user, Lead $lead): bool
    {
        // If user has no client association, they can't own leads
        if (!property_exists($user, 'client_id') || $user->client_id === null) {
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

