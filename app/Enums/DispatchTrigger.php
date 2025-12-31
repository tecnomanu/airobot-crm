<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * DispatchTrigger represents what triggered a lead dispatch.
 */
enum DispatchTrigger: string
{
    case ON_INTERESTED = 'on_interested';
    case ON_NOT_INTERESTED = 'on_not_interested';
    case ON_NO_RESPONSE = 'on_no_response';
    case ON_CLOSE = 'on_close';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::ON_INTERESTED => 'Al Interesado',
            self::ON_NOT_INTERESTED => 'Al No Interesado',
            self::ON_NO_RESPONSE => 'Sin Respuesta',
            self::ON_CLOSE => 'Al Cerrar',
            self::MANUAL => 'Manual',
        };
    }

    /**
     * Get trigger from close reason.
     */
    public static function fromCloseReason(LeadCloseReason $reason): ?self
    {
        return match ($reason->toIntentionType()) {
            'interested' => self::ON_INTERESTED,
            'not_interested' => self::ON_NOT_INTERESTED,
            'no_response' => self::ON_NO_RESPONSE,
            default => null,
        };
    }
}

