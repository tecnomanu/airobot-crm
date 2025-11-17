<?php

namespace App\Models;

use App\Enums\LeadAutomationStatus;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'phone',
        'name',
        'city',
        'country',
        'option_selected',
        'campaign_id',
        'status',
        'source',
        'sent_at',
        'intention',
        'intention_origin',
        'intention_status',
        'intention_decided_at',
        'exported_at',
        'notes',
        'tags',
        'webhook_sent',
        'webhook_result',
        'automation_status',
        'next_action_at',
        'last_automation_run_at',
        'automation_attempts',
        'automation_error',
        'created_by',
    ];

    protected $casts = [
        // option_selected y source son strings flexibles para integraciones custom
        'status' => LeadStatus::class,
        'automation_status' => LeadAutomationStatus::class,
        'intention_origin' => LeadIntentionOrigin::class,
        'intention_status' => LeadIntentionStatus::class,
        'sent_at' => 'datetime',
        'intention_decided_at' => 'datetime',
        'exported_at' => 'datetime',
        'next_action_at' => 'datetime',
        'last_automation_run_at' => 'datetime',
        'webhook_sent' => 'boolean',
        'tags' => 'array',
    ];

    /**
     * Relación con la campaña a la que pertenece el lead
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relación con el usuario que creó el lead
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el cliente a través de la campaña
     */
    public function client(): BelongsTo
    {
        return $this->campaign->client();
    }

    /**
     * Relación con el historial de llamadas del lead
     */
    public function callHistories(): HasMany
    {
        return $this->hasMany(CallHistory::class);
    }

    /**
     * Relación con las interacciones del lead
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(LeadInteraction::class);
    }
}
