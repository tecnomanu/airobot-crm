<?php

namespace App\Enums;

enum LeadSource: string
{
    case WEBHOOK_INICIAL = 'webhook_inicial';
    case WHATSAPP = 'whatsapp';
    case AGENTE_IA = 'agente_ia';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::WEBHOOK_INICIAL => 'Webhook Inicial',
            self::WHATSAPP => 'WhatsApp',
            self::AGENTE_IA => 'Agente IA',
            self::MANUAL => 'Manual',
        };
    }
}
