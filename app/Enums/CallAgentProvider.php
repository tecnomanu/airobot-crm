<?php

namespace App\Enums;

enum CallAgentProvider: string
{
    case RETELL = 'retell';
    case VAPI = 'vapi';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::RETELL => 'Retell',
            self::VAPI => 'Vapi',
            self::OTRO => 'Otro',
        };
    }
}
