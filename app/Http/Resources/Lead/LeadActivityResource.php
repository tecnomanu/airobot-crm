<?php

namespace App\Http\Resources\Lead;

use App\Models\Lead\LeadCall;
use App\Models\Lead\LeadMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for LeadActivity (polymorphic timeline)
 * Replaces LeadInteractionResource
 */
class LeadActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subject = $this->subject;
        $subjectData = $this->getSubjectData($subject);

        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'client_id' => $this->client_id,
            'subject_type' => $this->subject_type,
            'subject_type_label' => $this->getSubjectTypeLabel(),
            'subject_id' => $this->subject_id,
            'subject' => $subjectData,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }

    protected function getSubjectTypeLabel(): string
    {
        return match ($this->subject_type) {
            LeadCall::class, 'App\\Models\\Lead\\LeadCall' => 'call',
            LeadMessage::class, 'App\\Models\\Lead\\LeadMessage' => 'message',
            default => 'unknown',
        };
    }

    protected function getSubjectData($subject): ?array
    {
        if (! $subject) {
            return null;
        }

        if ($subject instanceof LeadCall) {
            return [
                'id' => $subject->id,
                'duration_seconds' => $subject->duration_seconds,
                'duration_formatted' => $this->formatDuration($subject->duration_seconds),
                'cost' => $subject->cost,
                'recording_url' => $subject->recording_url,
                'status' => $subject->status?->value ?? $subject->status,
                'status_label' => $subject->status?->label() ?? $subject->status,
                'direction' => $subject->direction,
                'call_date' => $subject->call_date?->toIso8601String(),
                'transcript' => $subject->transcript,
            ];
        }

        if ($subject instanceof LeadMessage) {
            return [
                'id' => $subject->id,
                'content' => $subject->content,
                'channel' => $subject->channel?->value,
                'channel_label' => $subject->channel?->label(),
                'direction' => $subject->direction?->value,
                'direction_label' => $subject->direction?->label(),
                'status' => $subject->status?->value,
                'status_label' => $subject->status?->label(),
                'phone' => $subject->phone,
                'external_provider_id' => $subject->external_provider_id,
            ];
        }

        return null;
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

