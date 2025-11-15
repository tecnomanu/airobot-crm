<?php

namespace App\Models;

use App\Enums\CampaignActionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignOption extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'option_key',
        'action',
        'source_id',
        'template_id',
        'message',
        'delay',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'delay' => 'integer',
        'action' => CampaignActionType::class,
    ];

    /**
     * Relación con la campaña
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relación con la fuente (WhatsApp o Webhook)
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Relación con el template de WhatsApp
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CampaignWhatsappTemplate::class, 'template_id');
    }
}
