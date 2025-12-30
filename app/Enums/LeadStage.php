<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * LeadStage represents the unified pipeline stage for UI display.
 *
 * This enum provides a single source of truth for lead stages,
 * derived from the combination of status, automation_status, and intention_status.
 *
 * Stages flow:
 * INBOX → QUALIFYING → SALES_READY → CLOSED
 *                  ↘ NOT_INTERESTED → CLOSED
 */
enum LeadStage: string
{
    case INBOX = 'inbox';
    case QUALIFYING = 'qualifying';
    case SALES_READY = 'sales_ready';
    case NOT_INTERESTED = 'not_interested';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::INBOX => 'Inbox',
            self::QUALIFYING => 'Calificando',
            self::SALES_READY => 'Listo para Ventas',
            self::NOT_INTERESTED => 'No Interesado',
            self::CLOSED => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INBOX => 'blue',
            self::QUALIFYING => 'yellow',
            self::SALES_READY => 'green',
            self::NOT_INTERESTED => 'red',
            self::CLOSED => 'gray',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INBOX => 'New leads pending initial processing or automation',
            self::QUALIFYING => 'Leads being contacted and awaiting intention confirmation',
            self::SALES_READY => 'Leads confirmed interested, ready for human handoff',
            self::NOT_INTERESTED => 'Leads that declined or showed no interest',
            self::CLOSED => 'Completed leads (won, lost, or no response)',
        };
    }

    /**
     * Derive stage from lead's current field values.
     *
     * Priority order:
     * 1. Explicit closed status
     * 2. Finalized intention (interested → sales_ready, not_interested → not_interested)
     * 3. Pending intention (in qualifying flow)
     * 4. Automation pending/skipped (inbox)
     * 5. Default inbox
     */
    public static function fromLead(
        ?LeadStatus $status,
        ?LeadAutomationStatus $automationStatus,
        ?LeadIntentionStatus $intentionStatus,
        ?string $intention
    ): self {
        // Closed status takes absolute priority
        if ($status === LeadStatus::CLOSED) {
            return self::CLOSED;
        }

        // Invalid leads are closed
        if ($status === LeadStatus::INVALID) {
            return self::CLOSED;
        }

        // Finalized intention determines if sales-ready or not interested
        if ($intentionStatus === LeadIntentionStatus::FINALIZED) {
            if ($intention === 'interested' || $intention === LeadIntention::INTERESTED->value) {
                return self::SALES_READY;
            }

            if ($intention === 'not_interested' || $intention === LeadIntention::NOT_INTERESTED->value) {
                return self::NOT_INTERESTED;
            }
        }

        // Sent to client is also sales ready (completed flow)
        if ($intentionStatus === LeadIntentionStatus::SENT_TO_CLIENT) {
            return self::SALES_READY;
        }

        // Pending intention means we're in qualifying stage
        if ($intentionStatus === LeadIntentionStatus::PENDING) {
            return self::QUALIFYING;
        }

        // Automation in progress or completed but awaiting intention
        if ($automationStatus === LeadAutomationStatus::PROCESSING
            || $automationStatus === LeadAutomationStatus::COMPLETED) {
            return self::QUALIFYING;
        }

        // Automation pending or skipped - still in inbox
        if ($automationStatus === LeadAutomationStatus::PENDING
            || $automationStatus === LeadAutomationStatus::SKIPPED
            || $automationStatus === null) {
            return self::INBOX;
        }

        // Failed automation - back to inbox for retry
        if ($automationStatus === LeadAutomationStatus::FAILED) {
            return self::INBOX;
        }

        // Default: inbox
        return self::INBOX;
    }

    /**
     * Map tab to stage (for backwards compatibility with UI tabs).
     */
    public static function fromTab(LeadManagerTab|string $tab): self
    {
        $tabEnum = $tab instanceof LeadManagerTab
            ? $tab
            : (LeadManagerTab::tryFrom($tab) ?? LeadManagerTab::default());

        return match ($tabEnum) {
            LeadManagerTab::INBOX => self::INBOX,
            LeadManagerTab::ACTIVE => self::QUALIFYING,
            LeadManagerTab::SALES_READY => self::SALES_READY,
            LeadManagerTab::CLOSED => self::CLOSED,
            LeadManagerTab::ERRORS => self::INBOX, // Errors are shown in inbox with filter
        };
    }

    /**
     * Get the tab this stage maps to (for UI compatibility).
     */
    public function toTab(): LeadManagerTab
    {
        return match ($this) {
            self::INBOX => LeadManagerTab::INBOX,
            self::QUALIFYING => LeadManagerTab::ACTIVE,
            self::SALES_READY => LeadManagerTab::SALES_READY,
            self::NOT_INTERESTED => LeadManagerTab::CLOSED,
            self::CLOSED => LeadManagerTab::CLOSED,
        };
    }

    /**
     * Get the tab name as string (for backwards compatibility).
     */
    public function toTabValue(): string
    {
        return $this->toTab()->value;
    }

    /**
     * Check if this stage allows automation retry.
     */
    public function canRetryAutomation(): bool
    {
        return $this === self::INBOX;
    }

    /**
     * Check if this stage represents an active/open lead.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::INBOX, self::QUALIFYING], true);
    }

    /**
     * Check if this stage represents a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::SALES_READY, self::NOT_INTERESTED, self::CLOSED], true);
    }
}

