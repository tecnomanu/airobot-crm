<?php

namespace App\Http\Resources\Lead;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadDispatchAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'trigger' => $this->trigger?->value,
            'trigger_label' => $this->trigger?->label(),
            'destination_id' => $this->destination_id,
            'request_url' => $this->request_url,
            'request_method' => $this->request_method,
            'response_status' => $this->response_status,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'attempt_no' => $this->attempt_no,
            'next_retry_at' => $this->next_retry_at?->toIso8601String(),
            'error_message' => $this->error_message,
            'can_retry' => $this->can_retry,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

