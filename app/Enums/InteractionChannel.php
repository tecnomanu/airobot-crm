<?php

namespace App\Enums;

enum InteractionChannel: string
{
    case WHATSAPP = 'whatsapp';
    case CALL = 'call';
    case EMAIL = 'email';
    case SMS = 'sms';
    case WEB = 'web';

    public function label(): string
    {
        return match ($this) {
            self::WHATSAPP => 'WhatsApp',
            self::CALL => 'Llamada',
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::WEB => 'Web',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::WHATSAPP => 'message-circle',
            self::CALL => 'phone',
            self::EMAIL => 'mail',
            self::SMS => 'message-square',
            self::WEB => 'globe',
        };
    }
}
