<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activa',
            self::PAUSED => 'Pausada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::PAUSED => 'yellow',
        };
    }
}
