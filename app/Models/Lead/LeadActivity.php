<?php

namespace App\Models\Lead;

use App\Models\Client\Client;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * LeadActivity - Unified Timeline for Lead Events
 *
 * This model serves as the central hub for all lead activities.
 * Each activity points to a specific subject (LeadCall, LeadMessage, etc.)
 * via polymorphic relations.
 */
class LeadActivity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lead_id',
        'client_id',
        'subject_type',
        'subject_id',
    ];

    public const RELATION_LEAD = 'lead';
    public const RELATION_CLIENT = 'client';
    public const RELATION_SUBJECT = 'subject';

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('subject_type', $type);
    }

    public function scopeCalls($query)
    {
        return $query->where('subject_type', LeadCall::class);
    }

    public function scopeMessages($query)
    {
        return $query->where('subject_type', LeadMessage::class);
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}

