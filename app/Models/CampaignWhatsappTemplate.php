<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignWhatsappTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'code',
        'name',
        'body',
        'attachments',
        'is_default',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Relación con la campaña
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}

