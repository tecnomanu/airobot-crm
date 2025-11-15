<?php

namespace App\Models;

use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadInteraction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lead_id',
        'campaign_id',
        'channel',
        'direction',
        'content',
        'payload',
        'external_id',
        'phone',
    ];

    protected $casts = [
        'channel' => InteractionChannel::class,
        'direction' => InteractionDirection::class,
        'payload' => 'array',
    ];

    /**
     * Relación con el lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relación con la campaña
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}

