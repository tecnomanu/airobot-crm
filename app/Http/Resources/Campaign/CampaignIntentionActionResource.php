<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignIntentionActionResource extends JsonResource
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
            'campaign_id' => $this->campaign_id,
            'intention_type' => $this->intention_type,
            'action_type' => $this->action_type,
            'webhook_id' => $this->webhook_id,
            'webhook' => $this->whenLoaded('webhook'),
            'google_integration_id' => $this->google_integration_id,
            'google_integration' => $this->whenLoaded('googleIntegration'),
            'google_spreadsheet_id' => $this->google_spreadsheet_id,
            'google_sheet_name' => $this->google_sheet_name,
            'enabled' => $this->enabled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
