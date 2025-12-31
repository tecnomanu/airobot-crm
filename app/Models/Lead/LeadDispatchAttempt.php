<?php

namespace App\Models\Lead;

use App\Enums\DispatchStatus;
use App\Enums\DispatchTrigger;
use App\Enums\DispatchType;
use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadDispatchAttempt extends Model
{
    use HasUuids;

    protected $fillable = [
        'lead_id',
        'client_id',
        'campaign_id',
        'type',
        'trigger',
        'destination_id',
        'request_payload',
        'request_url',
        'request_method',
        'response_status',
        'response_body',
        'status',
        'attempt_no',
        'next_retry_at',
        'error_message',
    ];

    protected $casts = [
        'type' => DispatchType::class,
        'trigger' => DispatchTrigger::class,
        'status' => DispatchStatus::class,
        'request_payload' => 'array',
        'next_retry_at' => 'datetime',
        'attempt_no' => 'integer',
        'response_status' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'attempt_no' => 1,
        'request_method' => 'POST',
    ];

    // Relation constants
    public const RELATION_LEAD = 'lead';
    public const RELATION_CLIENT = 'client';
    public const RELATION_CAMPAIGN = 'campaign';

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getCanRetryAttribute(): bool
    {
        return $this->status?->canRetry() ?? false;
    }

    public function getIsSuccessAttribute(): bool
    {
        return $this->status === DispatchStatus::SUCCESS;
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === DispatchStatus::FAILED;
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeSuccessful($query)
    {
        return $query->where('status', DispatchStatus::SUCCESS->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', DispatchStatus::FAILED->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', DispatchStatus::PENDING->value);
    }

    public function scopeRetrying($query)
    {
        return $query->where('status', DispatchStatus::RETRYING->value);
    }

    public function scopeReadyForRetry($query)
    {
        return $query->where('status', DispatchStatus::RETRYING->value)
            ->where('next_retry_at', '<=', now());
    }

    public function scopeByType($query, DispatchType $type)
    {
        return $query->where('type', $type->value);
    }

    public function scopeByTrigger($query, DispatchTrigger $trigger)
    {
        return $query->where('trigger', $trigger->value);
    }

    public function scopeForLead($query, $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    /**
     * Check if a successful dispatch already exists for this lead+trigger+destination.
     */
    public function scopeExistsSuccessful($query, $leadId, DispatchTrigger $trigger, ?string $destinationId = null)
    {
        return $query->where('lead_id', $leadId)
            ->where('trigger', $trigger->value)
            ->when($destinationId, fn($q) => $q->where('destination_id', $destinationId))
            ->where('status', DispatchStatus::SUCCESS->value);
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Mark attempt as successful.
     */
    public function markSuccess(int $responseStatus, ?string $responseBody = null): void
    {
        $this->update([
            'status' => DispatchStatus::SUCCESS,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'next_retry_at' => null,
        ]);
    }

    /**
     * Mark attempt as failed with optional retry scheduling.
     */
    public function markFailed(
        ?int $responseStatus = null,
        ?string $responseBody = null,
        ?string $errorMessage = null,
        bool $scheduleRetry = true,
        int $retryDelayMinutes = 5
    ): void {
        $this->update([
            'status' => $scheduleRetry ? DispatchStatus::RETRYING : DispatchStatus::FAILED,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
            'next_retry_at' => $scheduleRetry ? now()->addMinutes($retryDelayMinutes) : null,
        ]);
    }

    /**
     * Increment attempt count for retry.
     */
    public function incrementAttempt(): void
    {
        $this->increment('attempt_no');
        $this->update(['status' => DispatchStatus::PENDING]);
    }
}

