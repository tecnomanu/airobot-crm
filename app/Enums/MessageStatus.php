<?php

namespace App\Enums;

enum MessageStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::SENT => 'Enviado',
            self::DELIVERED => 'Entregado',
            self::READ => 'Leído',
            self::FAILED => 'Fallido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::SENT => 'blue',
            self::DELIVERED => 'green',
            self::READ => 'emerald',
            self::FAILED => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::SENT => 'check',
            self::DELIVERED => 'check-check',
            self::READ => 'eye',
            self::FAILED => 'x-circle',
        };
    }
}

