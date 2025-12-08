<?php

namespace App\Models\Campaign;

use App\Models\Integration\Source;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignWhatsappAgent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'name',
        'source_id',
        'config',
        'enabled',
    ];

    protected $casts = [
        'config' => 'array',
        'enabled' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}

