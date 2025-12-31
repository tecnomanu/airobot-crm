<?php

declare(strict_types=1);

namespace App\Events\Lead;

use App\Enums\LeadStage;
use App\Models\Lead\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Domain event emitted when a lead's stage changes.
 *
 * This event is triggered when the computed stage transitions,
 * regardless of which underlying field changed (status, automation_status, intention_status).
 */
class LeadStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly LeadStage $previousStage,
        public readonly LeadStage $newStage
    ) {}

    public function movedForward(): bool
    {
        $stageOrder = [
            LeadStage::INBOX->value => 0,
            LeadStage::QUALIFYING->value => 1,
            LeadStage::SALES_READY->value => 2,
            LeadStage::NOT_INTERESTED->value => 2,
            LeadStage::CLOSED->value => 3,
        ];

        return $stageOrder[$this->newStage->value] > $stageOrder[$this->previousStage->value];
    }
}

