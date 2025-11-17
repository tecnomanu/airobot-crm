<?php

namespace App\Enums;

enum InteractionDirection: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';

    public function label(): string
    {
        return match ($this) {
            self::INBOUND => 'Entrante',
            self::OUTBOUND => 'Saliente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INBOUND => 'blue',
            self::OUTBOUND => 'green',
        };
    }
}
