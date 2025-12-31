<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\LeadIntention;
use App\Enums\LeadIntentionStatus;
use App\Models\Lead\Lead;
use App\Services\Lead\LeadAssignmentService;
use Illuminate\Support\Facades\Log;

/**
 * Observer for Lead model.
 *
 * Handles automatic assignment when leads transition to Sales Ready.
 */
class LeadObserver
{
    public function __construct(
        private LeadAssignmentService $assignmentService
    ) {}

    /**
     * Handle the Lead "updated" event.
     *
     * Check if lead transitioned to Sales Ready and auto-assign.
     */
    public function updated(Lead $lead): void
    {
        // Check if lead just became Sales Ready
        if ($this->justBecameSalesReady($lead)) {
            Log::info('Lead transitioned to Sales Ready, triggering auto-assignment', [
                'lead_id' => $lead->id,
                'intention' => $lead->intention,
                'intention_status' => $lead->intention_status?->value,
            ]);

            $this->assignmentService->assignOnSalesReady($lead);
        }
    }

    /**
     * Handle the Lead "created" event.
     *
     * If a lead is created already in Sales Ready state, assign immediately.
     */
    public function created(Lead $lead): void
    {
        if ($this->isSalesReady($lead)) {
            Log::info('Lead created in Sales Ready state, triggering auto-assignment', [
                'lead_id' => $lead->id,
            ]);

            $this->assignmentService->assignOnSalesReady($lead);
        }
    }

    /**
     * Check if the lead just transitioned to Sales Ready.
     */
    private function justBecameSalesReady(Lead $lead): bool
    {
        // Check if intention_status just became FINALIZED
        $intentionStatusChanged = $lead->wasChanged('intention_status');
        $intentionChanged = $lead->wasChanged('intention');

        if (! $intentionStatusChanged && ! $intentionChanged) {
            return false;
        }

        // Must now be in Sales Ready state
        return $this->isSalesReady($lead);
    }

    /**
     * Check if lead is in Sales Ready state.
     */
    private function isSalesReady(Lead $lead): bool
    {
        return $lead->intention_status === LeadIntentionStatus::FINALIZED
            && $lead->intention === LeadIntention::INTERESTED->value;
    }
}

