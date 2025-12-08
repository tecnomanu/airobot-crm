<?php

namespace App\Models\Lead;

use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Models\Campaign\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * LeadMessage - WhatsApp/SMS Communication Records
 *
 * Contains all message-specific data:
 * - Content and direction (inbound/outbound)
 * - Channel (WhatsApp, SMS)
 * - Delivery status tracking
 * - Provider integration data
 *
 * Each LeadMessage automatically creates a LeadActivity entry for the timeline.
 */
class LeadMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lead_id',
        'campaign_id',
        'phone',
        'content',
        'direction',
        'channel',
        'status',
        'external_provider_id',
        'metadata',
        'attachments',
        'created_by',
    ];

    protected $casts = [
        'direction' => MessageDirection::class,
        'channel' => MessageChannel::class,
        'status' => MessageStatus::class,
        'metadata' => 'array',
        'attachments' => 'array',
    ];

    public const RELATION_LEAD = 'lead';
    public const RELATION_CAMPAIGN = 'campaign';
    public const RELATION_ACTIVITY = 'activity';
    public const RELATION_CREATOR = 'creator';

    protected static function booted(): void
    {
        static::created(function (LeadMessage $message) {
            $message->createActivity();
        });

        static::deleted(function (LeadMessage $message) {
            $message->activity?->delete();
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

    public function scopeByChannel($query, MessageChannel $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeWhatsapp($query)
    {
        return $query->where('channel', MessageChannel::WHATSAPP);
    }

    public function scopeSms($query)
    {
        return $query->where('channel', MessageChannel::SMS);
    }

    public function scopeByDirection($query, MessageDirection $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', MessageDirection::INBOUND);
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', MessageDirection::OUTBOUND);
    }

    public function scopeWithStatus($query, MessageStatus $status)
    {
        return $query->where('status', $status);
    }

    public function getIsFromLeadAttribute(): bool
    {
        return $this->direction === MessageDirection::INBOUND;
    }

    public function getIsDeliveredAttribute(): bool
    {
        return in_array($this->status, [
            MessageStatus::DELIVERED,
            MessageStatus::READ,
        ]);
    }
}

