<?php

namespace App\Enums;

enum ClientType: string
{
    case INTERNAL = 'internal';   // AirRobot HQ - owner of the system
    case DIRECT = 'direct';       // Direct client (current behavior)
    case RESELLER = 'reseller';   // Future: reseller with sub-clients
    case FRANCHISE = 'franchise'; // Future: franchise partner

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Interno',
            self::DIRECT => 'Cliente Directo',
            self::RESELLER => 'Reseller',
            self::FRANCHISE => 'Franquicia',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INTERNAL => 'purple',
            self::DIRECT => 'blue',
            self::RESELLER => 'orange',
            self::FRANCHISE => 'green',
        };
    }

    /**
     * Check if this client type can have sub-clients.
     */
    public function canHaveSubClients(): bool
    {
        return match ($this) {
            self::INTERNAL, self::RESELLER, self::FRANCHISE => true,
            self::DIRECT => false,
        };
    }
}

