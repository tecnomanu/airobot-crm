<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estados de una fuente/source
 */
enum SourceStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ERROR = 'error';
    case PENDING_SETUP = 'pending_setup';

    /**
     * Obtiene label legible para UI
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::ERROR => 'Error',
            self::PENDING_SETUP => 'Pendiente configuración',
        };
    }

    /**
     * Retorna color para UI
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::ERROR => 'red',
            self::PENDING_SETUP => 'yellow',
        };
    }
}
