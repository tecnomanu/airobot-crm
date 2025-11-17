<?php

namespace App\Http\Resources\CallHistory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'campaign_id' => $this->campaign_id,
            'client_id' => $this->client_id,
            'call_date' => $this->call_date->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'duration_minutes' => round($this->duration_seconds / 60, 2),
            'cost' => (float) $this->cost,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'lead_id' => $this->lead_id,
            'provider' => $this->provider,
            'call_id_external' => $this->call_id_external,
            'notes' => $this->notes,
            'recording_url' => $this->recording_url,
            'transcript' => $this->transcript,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relaciones opcionales
            'campaign' => $this->whenLoaded('campaign', function () {
                return [
                    'id' => $this->campaign->id,
                    'name' => $this->campaign->name,
                ];
            }),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ];
            }),
            'lead' => $this->whenLoaded('lead', function () {
                return [
                    'id' => $this->lead->id,
                    'phone' => $this->lead->phone,
                    'name' => $this->lead->name,
                ];
            }),
        ];
    }
}
