<?php

namespace App\Enums;

enum LeadIntention: string
{
    case INTERESTED = 'interested';
    case NOT_INTERESTED = 'not_interested';

    /**
     * Label legible para mostrar en UI
     */
    public function label(): string
    {
        return match ($this) {
            self::INTERESTED => 'Interested',
            self::NOT_INTERESTED => 'Not Interested',
        };
    }

    /**
     * Color para badges/UI
     */
    public function color(): string
    {
        return match ($this) {
            self::INTERESTED => 'green',
            self::NOT_INTERESTED => 'red',
        };
    }

    /**
     * Emoji representativo
     */
    public function emoji(): string
    {
        return match ($this) {
            self::INTERESTED => '✅',
            self::NOT_INTERESTED => '❌',
        };
    }

    /**
     * Descripción del estado
     */
    public function description(): string
    {
        return match ($this) {
            self::INTERESTED => 'Lead is interested in the product/service',
            self::NOT_INTERESTED => 'Lead is not interested',
        };
    }
}

