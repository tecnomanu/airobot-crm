<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Enums\LeadIntention;
use App\Enums\LeadIntentionStatus;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignAssignee;
use App\Models\Campaign\CampaignAssignmentCursor;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for assigning leads to sales representatives using round-robin.
 *
 * RULE: No Sales Ready lead can exist without an assignee (unless explicit config error).
 */
class LeadAssignmentService
{
    /**
     * Assign a lead when it transitions to Sales Ready.
     *
     * Called automatically when intention_status becomes FINALIZED and intention is INTERESTED.
     *
     * @return bool True if assigned, false if no assignees configured (error state)
     */
    public function assignOnSalesReady(Lead $lead): bool
    {
        // Only assign if lead is actually sales ready
        if (! $this->isSalesReady($lead)) {
            return false;
        }

        // Already assigned? Skip
        if ($lead->assigned_to !== null) {
            Log::debug('Lead already assigned, skipping', [
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_to,
            ]);

            return true;
        }

        $campaign = $lead->campaign;

        if (! $campaign) {
            $this->markAssignmentError($lead, 'Lead has no associated campaign');

            return false;
        }

        return $this->assignFromCampaign($lead, $campaign);
    }

    /**
     * Manually assign a lead to a specific user.
     */
    public function assignManually(Lead $lead, int $userId, ?int $assignedById = null): bool
    {
        return DB::transaction(function () use ($lead, $userId, $assignedById) {
            $previousAssignee = $lead->assigned_to;

            $lead->update([
                'assigned_to' => $userId,
                'assigned_at' => now(),
                'assignment_error' => null,
            ]);

            // Log activity
            $this->logAssignmentActivity($lead, $userId, 'manual', $assignedById, $previousAssignee);

            Log::info('Lead manually assigned', [
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'assigned_by' => $assignedById,
                'previous_assignee' => $previousAssignee,
            ]);

            return true;
        });
    }

    /**
     * Unassign a lead.
     */
    public function unassign(Lead $lead, ?int $unassignedById = null): bool
    {
        $previousAssignee = $lead->assigned_to;

        $lead->update([
            'assigned_to' => null,
            'assigned_at' => null,
        ]);

        $this->logAssignmentActivity($lead, null, 'unassigned', $unassignedById, $previousAssignee);

        return true;
    }

    /**
     * Get the next assignee for a campaign using round-robin.
     */
    public function getNextAssignee(Campaign $campaign): ?CampaignAssignee
    {
        $assignees = $campaign->activeAssignees()->get();

        if ($assignees->isEmpty()) {
            return null;
        }

        $cursor = $this->getOrCreateCursor($campaign);
        $currentIndex = $cursor->current_index;

        // Handle case where cursor is out of bounds (assignees removed)
        if ($currentIndex >= $assignees->count()) {
            $cursor->reset();
            $currentIndex = 0;
        }

        return $assignees->get($currentIndex);
    }

    /**
     * Sync campaign assignees from an array of user IDs.
     */
    public function syncAssignees(Campaign $campaign, array $userIds): void
    {
        DB::transaction(function () use ($campaign, $userIds) {
            // Remove existing assignees not in the new list
            $campaign->assignees()
                ->whereNotIn('user_id', $userIds)
                ->delete();

            // Add new assignees
            foreach ($userIds as $index => $userId) {
                CampaignAssignee::updateOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'user_id' => $userId,
                    ],
                    [
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            }

            // Reset cursor if assignees changed significantly
            $cursor = $campaign->assignmentCursor;
            if ($cursor && $cursor->current_index >= count($userIds)) {
                $cursor->reset();
            }

            Log::info('Campaign assignees synced', [
                'campaign_id' => $campaign->id,
                'assignee_count' => count($userIds),
            ]);
        });
    }

    /**
     * Check if a lead qualifies as Sales Ready.
     */
    public function isSalesReady(Lead $lead): bool
    {
        return $lead->intention_status === LeadIntentionStatus::FINALIZED
            && $lead->intention === LeadIntention::INTERESTED->value;
    }

    /**
     * Assign lead from campaign's assignee pool.
     */
    private function assignFromCampaign(Lead $lead, Campaign $campaign): bool
    {
        return DB::transaction(function () use ($lead, $campaign) {
            $assignee = $this->getNextAssignee($campaign);

            if (! $assignee) {
                $this->markAssignmentError($lead, 'No active assignees configured for campaign');

                Log::warning('Cannot assign lead: no assignees configured', [
                    'lead_id' => $lead->id,
                    'campaign_id' => $campaign->id,
                ]);

                return false;
            }

            // Assign the lead
            $lead->update([
                'assigned_to' => $assignee->user_id,
                'assigned_at' => now(),
                'assignment_error' => null,
            ]);

            // Advance the round-robin cursor
            $cursor = $this->getOrCreateCursor($campaign);
            $totalAssignees = $campaign->activeAssignees()->count();
            $cursor->advance($totalAssignees);

            // Log activity
            $this->logAssignmentActivity($lead, $assignee->user_id, 'auto_round_robin');

            Log::info('Lead auto-assigned via round-robin', [
                'lead_id' => $lead->id,
                'user_id' => $assignee->user_id,
                'user_name' => $assignee->user->name ?? 'Unknown',
                'campaign_id' => $campaign->id,
                'cursor_position' => $cursor->current_index,
            ]);

            return true;
        });
    }

    /**
     * Get or create the assignment cursor for a campaign.
     */
    private function getOrCreateCursor(Campaign $campaign): CampaignAssignmentCursor
    {
        return CampaignAssignmentCursor::firstOrCreate(
            ['campaign_id' => $campaign->id],
            ['current_index' => 0]
        );
    }

    /**
     * Mark a lead with an assignment error.
     */
    private function markAssignmentError(Lead $lead, string $error): void
    {
        $lead->update([
            'assignment_error' => $error,
        ]);

        Log::warning('Lead assignment error', [
            'lead_id' => $lead->id,
            'error' => $error,
        ]);
    }

    /**
     * Log assignment activity for audit trail.
     */
    private function logAssignmentActivity(
        Lead $lead,
        ?int $assignedToUserId,
        string $type,
        ?int $assignedByUserId = null,
        ?int $previousAssigneeId = null
    ): void {
        // Create a LeadActivity for the timeline
        // Using subject polymorphic to point to the lead itself for assignment events
        LeadActivity::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->resolved_client_id,
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
        ]);

        // Additional logging could be done here with a dedicated LeadAssignmentLog model
        // For now, we rely on the activity + application logs
    }
}

