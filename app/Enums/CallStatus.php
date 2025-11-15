<?php

namespace App\Enums;

enum CallStatus: string
{
    case COMPLETED = 'completed';
    case NO_ANSWER = 'no_answer';
    case HUNG_UP = 'hung_up';
    case FAILED = 'failed';
    case BUSY = 'busy';
    case VOICEMAIL = 'voicemail';

    public function label(): string
    {
        return match($this) {
            self::COMPLETED => 'Completada',
            self::NO_ANSWER => 'Sin Respuesta',
            self::HUNG_UP => 'Colgó',
            self::FAILED => 'Fallida',
            self::BUSY => 'Ocupado',
            self::VOICEMAIL => 'Buzón de Voz',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::COMPLETED => 'green',
            self::NO_ANSWER => 'yellow',
            self::HUNG_UP => 'orange',
            self::FAILED => 'red',
            self::BUSY => 'purple',
            self::VOICEMAIL => 'blue',
        };
    }
}

