<?php

declare(strict_types=1);

namespace App\Listeners\Lead;

use App\Events\Lead\LeadCreated;
use App\Events\Lead\LeadStageChanged;
use App\Events\LeadUpdated;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

/**
 * Listener that broadcasts lead changes to the frontend via WebSockets.
 *
 * This listener decouples domain events from broadcasting infrastructure,
 * keeping the service layer clean of broadcast concerns.
 */
class BroadcastLeadChanges implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle LeadCreated event.
     */
    public function handleLeadCreated(LeadCreated $event): void
    {
        $lead = $event->lead->load('campaign');

        broadcast(new LeadUpdated($lead, 'created'))->toOthers();

        Log::debug('Lead creation broadcasted', [
            'lead_id' => $lead->id,
            'source' => $event->source,
        ]);
    }

    /**
     * Handle LeadStageChanged event.
     */
    public function handleLeadStageChanged(LeadStageChanged $event): void
    {
        $lead = $event->lead->load('campaign');

        broadcast(new LeadUpdated($lead, 'updated'))->toOthers();

        Log::debug('Lead stage change broadcasted', [
            'lead_id' => $lead->id,
            'previous_stage' => $event->previousStage->value,
            'new_stage' => $event->newStage->value,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            LeadCreated::class => 'handleLeadCreated',
            LeadStageChanged::class => 'handleLeadStageChanged',
        ];
    }
}

