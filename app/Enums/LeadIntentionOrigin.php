<?php

namespace App\Enums;

enum LeadIntentionOrigin: string
{
    case WHATSAPP = 'whatsapp';
    case AGENT_IA = 'agent_ia';
    case IVR = 'ivr';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::WHATSAPP => 'WhatsApp',
            self::AGENT_IA => 'Agente IA',
            self::IVR => 'IVR',
            self::MANUAL => 'Manual',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WHATSAPP => 'green',
            self::AGENT_IA => 'blue',
            self::IVR => 'purple',
            self::MANUAL => 'gray',
        };
    }
}
