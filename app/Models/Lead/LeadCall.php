<?php

namespace App\Models\Lead;

use App\Enums\CallStatus;
use App\Models\Campaign\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * LeadCall - Detailed Call Records with Metrics
 *
 * Contains strict columns for:
 * - Duration metrics
 * - Cost/billing calculations (enables LeadCall::sum('cost'))
 * - Recording URLs
 * - Provider integration data (Retell, Vapi, etc.)
 *
 * Each LeadCall automatically creates a LeadActivity entry for the timeline.
 */
class LeadCall extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lead_id',
        'campaign_id',
        'phone',
        'duration_seconds',
        'cost',
        'call_date',
        'status',
        'provider',
        'retell_call_id',
        'recording_url',
        'transcript',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'call_date' => 'datetime',
        'duration_seconds' => 'integer',
        'cost' => 'decimal:4',
        'status' => CallStatus::class,
        'metadata' => 'array',
    ];

    public const RELATION_LEAD = 'lead';
    public const RELATION_CAMPAIGN = 'campaign';
    public const RELATION_ACTIVITY = 'activity';
    public const RELATION_CREATOR = 'creator';

    protected static function booted(): void
    {
        static::created(function (LeadCall $call) {
            $call->createActivity();
        });

        static::deleted(function (LeadCall $call) {
            $call->activity?->delete();
        });
    }

    public function createActivity(): LeadActivity
    {
        $lead = Lead::with('campaign')->find($this->lead_id);
        $clientId = $lead?->client_id ?? $lead?->campaign?->client_id;

        return $this->activity()->create([
            'lead_id' => $this->lead_id,
            'client_id' => $clientId,
        ]);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activity(): MorphOne
    {
        return $this->morphOne(LeadActivity::class, 'subject');
    }

    public function scopeWithStatus($query, CallStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', CallStatus::COMPLETED);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('call_date', [$startDate, $endDate]);
    }

    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getWasAnsweredAttribute(): bool
    {
        return in_array($this->status, [
            CallStatus::COMPLETED,
            CallStatus::HUNG_UP,
            CallStatus::VOICEMAIL,
        ]);
    }
}

