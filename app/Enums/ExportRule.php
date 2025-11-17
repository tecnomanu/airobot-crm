<?php

namespace App\Enums;

enum ExportRule: string
{
    case INTERESTED_ONLY = 'interested_only';
    case NOT_INTERESTED_ONLY = 'not_interested_only';
    case BOTH = 'both';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::INTERESTED_ONLY => 'Solo Interesados',
            self::NOT_INTERESTED_ONLY => 'Solo No Interesados',
            self::BOTH => 'Ambos',
            self::NONE => 'Ninguno (solo métricas)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INTERESTED_ONLY => 'Exportar únicamente leads con intención "interested"',
            self::NOT_INTERESTED_ONLY => 'Exportar únicamente leads con intención "not_interested"',
            self::BOTH => 'Exportar tanto interesados como no interesados',
            self::NONE => 'No exportar al cliente, mantener solo para métricas internas',
        };
    }
}
