<?php

namespace App\Jobs\Lead;

use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStatus;
use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Models\Lead\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job for checking leads with pending intentions
 * If they haven't responded in X hours, mark as "no_response"
 */
class CheckPendingIntentsJob implements ShouldQueue
{
    use Queueable;

    private int $timeoutHours;

    public function __construct(int $timeoutHours = 24)
    {
        $this->timeoutHours = $timeoutHours;
    }

    public function handle(): void
    {
        $cutoffTime = now()->subHours($this->timeoutHours);

        Log::info('Iniciando verificación de intenciones pendientes', [
            'timeout_hours' => $this->timeoutHours,
            'cutoff_time' => $cutoffTime->toDateTimeString(),
        ]);

        $pendingLeads = Lead::where('intention_status', LeadIntentionStatus::PENDING)
            ->whereNotNull('intention_origin')
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->get();

        $processed = 0;
        $noResponse = 0;

        foreach ($pendingLeads as $lead) {
            if ($this->hasTimedOut($lead, $cutoffTime)) {
                $this->markAsNoResponse($lead);
                $noResponse++;
            }
            $processed++;
        }

        Log::info('Verificación de intenciones completada', [
            'total_processed' => $processed,
            'marked_no_response' => $noResponse,
        ]);
    }

    private function hasTimedOut(Lead $lead, $cutoffTime): bool
    {
        // Get last outbound message (WhatsApp sent)
        $lastOutbound = $lead->messages()
            ->where('channel', MessageChannel::WHATSAPP)
            ->where('direction', MessageDirection::OUTBOUND)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastOutbound) {
            return false;
        }

        if ($lastOutbound->created_at->isAfter($cutoffTime)) {
            return false;
        }

        // Check if there's an inbound response after the outbound message
        $hasInboundAfter = $lead->messages()
            ->where('channel', MessageChannel::WHATSAPP)
            ->where('direction', MessageDirection::INBOUND)
            ->where('created_at', '>', $lastOutbound->created_at)
            ->exists();

        return ! $hasInboundAfter;
    }

    private function markAsNoResponse(Lead $lead): void
    {
        $lead->update([
            'intention' => 'no_response',
            'intention_status' => LeadIntentionStatus::FINALIZED,
            'intention_decided_at' => now(),
            'status' => LeadStatus::INVALID,
        ]);

        Log::info('Lead marcado como no responde por timeout', [
            'lead_id' => $lead->id,
            'phone' => $lead->phone,
            'campaign_id' => $lead->campaign_id,
        ]);
    }
}
