<?php

namespace App\Enums;

enum LeadOptionSelected: string
{
    case OPTION_1 = '1';
    case OPTION_2 = '2';
    case OPTION_I = 'i';
    case OPTION_T = 't';

    public function label(): string
    {
        return match($this) {
            self::OPTION_1 => 'Opción 1',
            self::OPTION_2 => 'Opción 2',
            self::OPTION_I => 'Opción I (Información)',
            self::OPTION_T => 'Opción T (Transferencia)',
        };
    }
}

