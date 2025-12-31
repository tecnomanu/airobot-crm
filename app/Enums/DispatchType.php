<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * DispatchType represents the type of external dispatch for lead data.
 */
enum DispatchType: string
{
    case WEBHOOK = 'webhook';
    case GOOGLE_SHEET = 'google_sheet';

    public function label(): string
    {
        return match ($this) {
            self::WEBHOOK => 'Webhook',
            self::GOOGLE_SHEET => 'Google Sheet',
        };
    }
}

