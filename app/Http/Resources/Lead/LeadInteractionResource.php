<?php

namespace App\Http\Resources\Lead;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadInteractionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'campaign_id' => $this->campaign_id,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'direction' => $this->direction->value,
            'direction_label' => $this->direction->label(),
            'content' => $this->content,
            'phone' => $this->phone,
            'external_id' => $this->external_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'payload' => $this->when($request->user()?->is_admin ?? false, $this->payload),
        ];
    }
}
