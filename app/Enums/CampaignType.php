<?php

namespace App\Enums;

enum CampaignType: string
{
    case FILTERING = 'filtering';
    case DIRECT_CALL = 'direct_call';

    public function label(): string
    {
        return match ($this) {
            self::FILTERING => 'Filtrado (IVR)',
            self::DIRECT_CALL => 'Llamado Directo',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FILTERING => 'Leads que llegan desde IVR externo con opción seleccionada (1, 2, i, t)',
            self::DIRECT_CALL => 'Leads cargados para ser llamados directamente (CSV, panel)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FILTERING => 'blue',
            self::DIRECT_CALL => 'green',
        };
    }
}
