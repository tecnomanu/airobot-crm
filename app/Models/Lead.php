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
        'client_id', // Direct client relationship (decoupled from campaign)
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
        'intention_webhook_sent',
        'intention_webhook_sent_at',
        'intention_webhook_response',
        'intention_webhook_status',
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
        'intention_webhook_sent' => 'boolean',
        'intention_webhook_sent_at' => 'datetime',
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
     * Direct relationship with Client (decoupled from campaign)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the client - either directly assigned or through campaign
     */
    public function getClientAttribute()
    {
        // If client_id is set, use direct relationship
        if ($this->client_id) {
            return $this->client()->first();
        }

        // Otherwise, get client through campaign
        return $this->campaign?->client;
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

    /**
     * Check if lead is owned by client (uploaded by client vs managed internally)
     */
    public function getIsClientOwnedAttribute(): bool
    {
        // Lead is client-owned if it has direct client_id but no campaign
        // or if source indicates manual upload by client
        return $this->client_id !== null && $this->campaign_id === null;
    }

    /**
     * Scope: Inbox/Raw - Newly ingested leads (IVR, Webhooks) not processed or qualified
     */
    public function scopeInbox($query)
    {
        return $query->where(function ($q) {
            $q->where('automation_status', LeadAutomationStatus::PENDING->value)
                ->orWhere('automation_status', LeadAutomationStatus::SKIPPED->value);
        })
            ->whereNull('intention_status')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Active Pipeline - Leads being processed or in automation flow
     */
    public function scopeActivePipeline($query)
    {
        return $query->where(function ($q) {
            $q->where('automation_status', LeadAutomationStatus::PROCESSING->value)
                ->orWhere('automation_status', LeadAutomationStatus::COMPLETED->value)
                ->orWhere(function ($subQuery) {
                    $subQuery->whereNotNull('intention_status')
                        ->where('intention_status', '!=', LeadIntentionStatus::FINALIZED->value);
                });
        })
            ->where('status', '!=', LeadStatus::CLOSED->value)
            ->orderBy('next_action_at', 'asc')
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Scope: Sales Ready - High intention leads requiring immediate human action
     */
    public function scopeSalesReady($query)
    {
        return $query->where('intention_status', LeadIntentionStatus::FINALIZED->value)
            ->where('status', '!=', LeadStatus::CLOSED->value)
            ->orderBy('intention_decided_at', 'desc');
    }

    /**
     * Scope: Filter by client (direct or through campaign)
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where(function ($q) use ($clientId) {
            $q->where('client_id', $clientId)
                ->orWhereHas('campaign', function ($subQuery) use ($clientId) {
                    $subQuery->where('client_id', $clientId);
                });
        });
    }

    /**
     * Scope: Has interactions (for filtering leads with conversation history)
     */
    public function scopeHasInteractions($query)
    {
        return $query->whereHas('interactions');
    }
}
