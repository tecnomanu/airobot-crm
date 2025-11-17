<?php

namespace App\Enums;

enum LeadStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case CONTACTED = 'contacted';
    case CLOSED = 'closed';
    case INVALID = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En Progreso',
            self::CONTACTED => 'Contactado',
            self::CLOSED => 'Cerrado',
            self::INVALID => 'Inválido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::CONTACTED => 'purple',
            self::CLOSED => 'green',
            self::INVALID => 'red',
        };
    }
}
