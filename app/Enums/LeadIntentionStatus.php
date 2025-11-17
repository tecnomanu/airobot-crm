<?php

namespace App\Enums;

enum LeadIntentionStatus: string
{
    case PENDING = 'pending';
    case FINALIZED = 'finalized';
    case SENT_TO_CLIENT = 'sent_to_client';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::FINALIZED => 'Finalizada',
            self::SENT_TO_CLIENT => 'Enviada al Cliente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::FINALIZED => 'blue',
            self::SENT_TO_CLIENT => 'green',
        };
    }
}
