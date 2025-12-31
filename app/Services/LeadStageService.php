<?php

namespace App\Services;

use App\Enums\LeadAutomationStatus;
use App\Enums\LeadCloseReason;
use App\Enums\LeadStage;
use App\Enums\LeadStatus;
use App\Exceptions\Business\LeadStageException;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadActivity;
use App\Services\Lead\LeadAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadStageService
{
    public function __construct(
        private LeadDispatchService $dispatchService,
        private ?LeadAssignmentService $assignmentService = null,
    ) {}

    /**
     * Transition lead to a new stage with validation.
     *
     * @throws LeadStageException
     */
    public function transitionTo(Lead $lead, LeadStage $newStage, ?string $reason = null): Lead
    {
        $currentStage = $lead->stage;

        // Validate transition
        $this->validateTransition($lead, $newStage);

        return DB::transaction(function () use ($lead, $newStage, $currentStage, $reason) {
            $lead->stage = $newStage;

            // Handle stage-specific logic
            match ($newStage) {
                LeadStage::QUALIFYING => $this->handleQualifyingTransition($lead),
                LeadStage::SALES_READY => $this->handleSalesReadyTransition($lead),
                LeadStage::CLOSED => throw new LeadStageException('Use closeLead() method to close leads'),
                default => null,
            };

            $lead->save();

            // Log activity
            $this->logStageChange($lead, $currentStage, $newStage, $reason);

            return $lead->fresh();
        });
    }

    /**
     * Close a lead with reason and notes.
     */
    public function closeLead(
        Lead $lead,
        LeadCloseReason $closeReason,
        ?string $closeNotes = null
    ): Lead {
        if ($lead->stage === LeadStage::CLOSED) {
            throw new LeadStageException('Lead is already closed');
        }

        return DB::transaction(function () use ($lead, $closeReason, $closeNotes) {
            $previousStage = $lead->stage;

            $lead->update([
                'stage' => LeadStage::CLOSED,
                'status' => LeadStatus::CLOSED,
                'closed_at' => now(),
                'close_reason' => $closeReason,
                'close_notes' => $closeNotes,
                'automation_status' => $lead->automation_status?->isActive()
                    ? LeadAutomationStatus::PAUSED
                    : $lead->automation_status,
            ]);

            // Log activity
            $this->logStageChange(
                $lead,
                $previousStage,
                LeadStage::CLOSED,
                "Closed: {$closeReason->label()}"
            );

            // Dispatch to external systems if configured
            if ($closeReason->shouldDispatch()) {
                $this->dispatchService->dispatchForCloseReason($lead, $closeReason);
            }

            Log::info('Lead closed', [
                'lead_id' => $lead->id,
                'close_reason' => $closeReason->value,
                'previous_stage' => $previousStage?->value,
            ]);

            return $lead->fresh();
        });
    }

    /**
     * Move lead to Sales Ready stage.
     */
    public function markSalesReady(Lead $lead, ?int $assignToUserId = null): Lead
    {
        return DB::transaction(function () use ($lead, $assignToUserId) {
            $previousStage = $lead->stage;

            $lead->update([
                'stage' => LeadStage::SALES_READY,
                'intention_status' => 'finalized',
                'intention_decided_at' => now(),
                'automation_status' => LeadAutomationStatus::COMPLETED,
            ]);

            // Assign to seller (round robin or specific)
            if ($assignToUserId) {
                $lead->update([
                    'assigned_to' => $assignToUserId,
                    'assigned_at' => now(),
                ]);
            } elseif ($lead->campaign_id && $this->assignmentService) {
                $this->assignmentService->assignNextSeller($lead);
            }

            $this->logStageChange($lead, $previousStage, LeadStage::SALES_READY, 'Marked as Sales Ready');

            return $lead->fresh();
        });
    }

    /**
     * Start automation for a lead.
     *
     * @throws LeadStageException
     */
    public function startAutomation(Lead $lead): Lead
    {
        if (!$lead->stage?->canStartAutomation()) {
            throw new LeadStageException(
                "Cannot start automation for lead in stage '{$lead->stage?->label()}'. " .
                "Automation can only be started for leads in Inbox or Qualifying stages."
            );
        }

        if (!$lead->automation_status?->canStart()) {
            throw new LeadStageException(
                "Cannot start automation with status '{$lead->automation_status?->label()}'"
            );
        }

        return DB::transaction(function () use ($lead) {
            $wasInbox = $lead->stage === LeadStage::INBOX;

            $lead->update([
                'stage' => LeadStage::QUALIFYING,
                'automation_status' => LeadAutomationStatus::RUNNING,
                'last_automation_run_at' => now(),
                'automation_error' => null,
            ]);

            if ($wasInbox) {
                $this->logStageChange($lead, LeadStage::INBOX, LeadStage::QUALIFYING, 'Automation started');
            }

            return $lead->fresh();
        });
    }

    /**
     * Pause automation for a lead.
     */
    public function pauseAutomation(Lead $lead): Lead
    {
        if (!$lead->automation_status?->canPause()) {
            throw new LeadStageException(
                "Cannot pause automation with status '{$lead->automation_status?->label()}'"
            );
        }

        $lead->update([
            'automation_status' => LeadAutomationStatus::PAUSED,
        ]);

        return $lead->fresh();
    }

    /**
     * Mark automation as failed.
     */
    public function failAutomation(Lead $lead, string $error): Lead
    {
        $lead->update([
            'automation_status' => LeadAutomationStatus::FAILED,
            'automation_error' => $error,
        ]);

        return $lead->fresh();
    }

    /**
     * Reopen a closed lead back to inbox.
     */
    public function reopenLead(Lead $lead): Lead
    {
        if ($lead->stage !== LeadStage::CLOSED) {
            throw new LeadStageException('Can only reopen closed leads');
        }

        return DB::transaction(function () use ($lead) {
            $lead->update([
                'stage' => LeadStage::INBOX,
                'status' => LeadStatus::PENDING,
                'closed_at' => null,
                'close_reason' => null,
                'close_notes' => null,
                'automation_status' => LeadAutomationStatus::PENDING,
            ]);

            $this->logStageChange($lead, LeadStage::CLOSED, LeadStage::INBOX, 'Reopened');

            return $lead->fresh();
        });
    }

    // ==========================================
    // PRIVATE METHODS
    // ==========================================

    private function validateTransition(Lead $lead, LeadStage $newStage): void
    {
        $currentStage = $lead->stage;

        // Cannot transition from CLOSED without using reopenLead
        if ($currentStage === LeadStage::CLOSED && $newStage !== LeadStage::CLOSED) {
            throw new LeadStageException('Cannot transition from CLOSED. Use reopenLead() instead.');
        }

        // Cannot skip to SALES_READY from INBOX
        if ($currentStage === LeadStage::INBOX && $newStage === LeadStage::SALES_READY) {
            throw new LeadStageException('Cannot skip directly from INBOX to SALES_READY. Lead must go through QUALIFYING first.');
        }
    }

    private function handleQualifyingTransition(Lead $lead): void
    {
        // Update automation status if pending
        if ($lead->automation_status === LeadAutomationStatus::PENDING) {
            $lead->automation_status = LeadAutomationStatus::RUNNING;
        }
    }

    private function handleSalesReadyTransition(Lead $lead): void
    {
        // Set intention fields if not set
        if (!$lead->intention_status) {
            $lead->intention_status = 'finalized';
        }
        if (!$lead->intention_decided_at) {
            $lead->intention_decided_at = now();
        }

        // Pause automation if running
        if ($lead->automation_status?->isActive()) {
            $lead->automation_status = LeadAutomationStatus::COMPLETED;
        }
    }

    private function logStageChange(
        Lead $lead,
        ?LeadStage $fromStage,
        LeadStage $toStage,
        ?string $reason = null
    ): void {
        $description = sprintf(
            'Stage changed: %s → %s',
            $fromStage?->label() ?? 'None',
            $toStage->label()
        );

        if ($reason) {
            $description .= " ({$reason})";
        }

        // Create activity record if client exists
        if ($lead->resolved_client_id) {
            LeadActivity::create([
                'lead_id' => $lead->id,
                'client_id' => $lead->resolved_client_id,
                'subject_type' => Lead::class,
                'subject_id' => $lead->id,
            ]);
        }

        Log::info('Lead stage transition', [
            'lead_id' => $lead->id,
            'from_stage' => $fromStage?->value,
            'to_stage' => $toStage->value,
            'reason' => $reason,
        ]);
    }
}

