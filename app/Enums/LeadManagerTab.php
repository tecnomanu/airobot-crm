<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tabs for the unified Leads Manager view.
 */
enum LeadManagerTab: string
{
    case INBOX = 'inbox';
    case ACTIVE = 'active';
    case SALES_READY = 'sales_ready';
    case CLOSED = 'closed';
    case ERRORS = 'errors';

    /**
     * Default tab when none specified.
     */
    public static function default(): self
    {
        return self::INBOX;
    }

    /**
     * Get all valid tab values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if a string is a valid tab.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Get tab from string, or default if invalid.
     */
    public static function fromStringOrDefault(?string $value): self
    {
        if ($value === null) {
            return self::default();
        }

        return self::tryFrom($value) ?? self::default();
    }

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::INBOX => 'Bandeja de Entrada',
            self::ACTIVE => 'Pipeline Activo',
            self::SALES_READY => 'Listos para Ventas',
            self::CLOSED => 'Cerrados',
            self::ERRORS => 'Con Errores',
        };
    }
}

