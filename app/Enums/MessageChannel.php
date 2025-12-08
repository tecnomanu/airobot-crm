<?php

namespace App\Enums;

enum MessageChannel: string
{
    case WHATSAPP = 'whatsapp';
    case SMS = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::WHATSAPP => 'WhatsApp',
            self::SMS => 'SMS',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::WHATSAPP => 'message-circle',
            self::SMS => 'message-square',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WHATSAPP => 'green',
            self::SMS => 'blue',
        };
    }
}

