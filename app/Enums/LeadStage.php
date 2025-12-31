<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * LeadStage represents the unified pipeline stage - PERSISTED in database.
 *
 * This is the SINGLE SOURCE OF TRUTH for lead stages.
 * The UI filters by this field directly.
 *
 * Flow:
 * INBOX → QUALIFYING → SALES_READY → CLOSED
 *                   ↘ CLOSED (with close_reason)
 */
enum LeadStage: string
{
    case INBOX = 'inbox';
    case QUALIFYING = 'qualifying';
    case SALES_READY = 'sales_ready';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::INBOX => 'Inbox',
            self::QUALIFYING => 'En Curso',
            self::SALES_READY => 'Sales Ready',
            self::CLOSED => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INBOX => 'blue',
            self::QUALIFYING => 'yellow',
            self::SALES_READY => 'green',
            self::CLOSED => 'gray',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INBOX => 'New leads pending initial processing',
            self::QUALIFYING => 'Leads being contacted via automation',
            self::SALES_READY => 'Qualified leads ready for human handoff',
            self::CLOSED => 'Completed leads (see close_reason for outcome)',
        };
    }

    /**
     * Check if automation can be started/resumed in this stage.
     */
    public function canStartAutomation(): bool
    {
        return in_array($this, [self::INBOX, self::QUALIFYING], true);
    }

    /**
     * Check if this stage represents an active/open lead.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::INBOX, self::QUALIFYING, self::SALES_READY], true);
    }

    /**
     * Check if this stage is terminal (closed).
     */
    public function isTerminal(): bool
    {
        return $this === self::CLOSED;
    }

    /**
     * Check if manual close is allowed from this stage.
     */
    public function canClose(): bool
    {
        return $this !== self::CLOSED;
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
            self::CLOSED => LeadManagerTab::CLOSED,
        };
    }

    /**
     * Get all stages that should show in a given tab.
     */
    public static function forTab(LeadManagerTab $tab): array
    {
        return match ($tab) {
            LeadManagerTab::INBOX => [self::INBOX],
            LeadManagerTab::ACTIVE => [self::QUALIFYING],
            LeadManagerTab::SALES_READY => [self::SALES_READY],
            LeadManagerTab::CLOSED => [self::CLOSED],
            LeadManagerTab::ERRORS => [self::INBOX, self::QUALIFYING], // Errors can be in any non-closed stage
        };
    }

    /**
     * Get all values as array for validation rules.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
