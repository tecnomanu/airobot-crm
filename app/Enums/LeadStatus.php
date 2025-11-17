<?php

namespace App\Enums;

enum LeadStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case CONTACTED = 'contacted';
    case CLOSED = 'closed';
    case INVALID = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::CONTACTED => 'Contacted',
            self::CLOSED => 'Closed',
            self::INVALID => 'Invalid',
        };
    }

    /**
     * Descripción de cada estado para entender su significado
     */
    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'New lead, not yet processed or contacted',
            self::IN_PROGRESS => 'Lead has shown interest and is being actively worked on',
            self::CONTACTED => 'Agent has personally reached out to the lead',
            self::CLOSED => 'Conversation completed or lead not interested',
            self::INVALID => 'Invalid or spam lead',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::CONTACTED => 'purple',
            self::CLOSED => 'green',
            self::INVALID => 'red',
        };
    }
}
