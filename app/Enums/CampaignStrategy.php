<?php

namespace App\Enums;

enum CampaignStrategy: string
{
    /**
     * Direct Strategy: Linear execution
     * Every lead triggers the same action immediately
     * Ideal for CSV lists, manual uploads, bulk campaigns
     */
    case DIRECT = 'direct';

    /**
     * Dynamic Strategy: Conditional execution
     * System waits for option_selected parameter and decides action based on mapping rules
     * Ideal for IVR flows, web forms with multiple options
     */
    case DYNAMIC = 'dynamic';

    public function label(): string
    {
        return match ($this) {
            self::DIRECT => 'Directa',
            self::DYNAMIC => 'Dinámica (Opciones)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DIRECT => 'Todos los leads ejecutan la misma acción automáticamente',
            self::DYNAMIC => 'La acción depende de la opción seleccionada por el lead (IVR, formulario)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DIRECT => 'green',
            self::DYNAMIC => 'blue',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DIRECT => 'zap',
            self::DYNAMIC => 'git-branch',
        };
    }

    public function isDirect(): bool
    {
        return $this === self::DIRECT;
    }

    public function isDynamic(): bool
    {
        return $this === self::DYNAMIC;
    }

    /**
     * Whether this strategy requires option_selected to process leads
     */
    public function requiresOptionSelection(): bool
    {
        return $this === self::DYNAMIC;
    }
}
