<?php

namespace App\Http\Resources\Lead;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for LeadCall (replaces CallHistoryResource)
 */
class LeadCallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'campaign_id' => $this->campaign_id,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->formatDuration($this->duration_seconds),
            'cost' => $this->cost,
            'cost_formatted' => $this->cost ? '$' . number_format($this->cost, 2) : null,
            'recording_url' => $this->recording_url,
            'retell_call_id' => $this->retell_call_id,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label() ?? $this->status,
            'direction' => $this->direction,
            'from_number' => $this->from_number,
            'to_number' => $this->to_number,
            'call_date' => $this->call_date?->toIso8601String(),
            'call_date_human' => $this->call_date?->diffForHumans(),
            'transcript' => $this->transcript,
            'summary' => $this->summary,
            'disconnection_reason' => $this->disconnection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relations
            'lead' => $this->whenLoaded('lead', fn() => new LeadResource($this->lead)),
        ];
    }

    protected function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return '0:00';
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $secs);
    }
}

