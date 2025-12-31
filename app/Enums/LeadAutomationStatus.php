<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * LeadAutomationStatus represents the state of the automation engine for a lead.
 *
 * This is separate from LeadStage - a lead can be in QUALIFYING stage
 * with automation PAUSED (waiting for human review).
 */
enum LeadAutomationStatus: string
{
    case PENDING = 'pending';       // Not yet started
    case RUNNING = 'running';       // Actively processing
    case WAITING = 'waiting';       // Waiting for next action (scheduled)
    case PAUSED = 'paused';         // Manually paused
    case COMPLETED = 'completed';   // Finished successfully
    case FAILED = 'failed';         // Failed with error
    case SKIPPED = 'skipped';       // Skipped (e.g., no campaign)

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::RUNNING => 'En Ejecución',
            self::WAITING => 'Esperando',
            self::PAUSED => 'Pausado',
            self::COMPLETED => 'Completado',
            self::FAILED => 'Fallido',
            self::SKIPPED => 'Omitido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'blue',
            self::WAITING => 'yellow',
            self::PAUSED => 'orange',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::SKIPPED => 'gray',
        };
    }

    /**
     * Whether automation is actively working on this lead.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::RUNNING, self::WAITING], true);
    }

    /**
     * Whether automation can be started/resumed.
     */
    public function canStart(): bool
    {
        return in_array($this, [self::PENDING, self::PAUSED, self::FAILED], true);
    }

    /**
     * Whether automation can be paused.
     */
    public function canPause(): bool
    {
        return in_array($this, [self::RUNNING, self::WAITING], true);
    }

    /**
     * Whether this represents an error state.
     */
    public function isError(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Whether this is a terminal state (no more automation).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::SKIPPED], true);
    }

    /**
     * Get all values as array for validation rules.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
