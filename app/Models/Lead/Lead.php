<?php

namespace App\Models\Lead;

use App\Enums\LeadAutomationStatus;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStatus;
use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'client_id',
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

    public const RELATION_CAMPAIGN = 'campaign';
    public const RELATION_CLIENT = 'client';
    public const RELATION_CREATOR = 'creator';
    public const RELATION_ACTIVITIES = 'activities';
    public const RELATION_CALLS = 'calls';
    public const RELATION_MESSAGES = 'messages';

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getResolvedClientAttribute(): ?Client
    {
        if ($this->client_id) {
            return $this->client;
        }
        return $this->campaign?->client;
    }

    public function getResolvedClientIdAttribute(): ?string
    {
        return $this->client_id ?? $this->campaign?->client_id;
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function calls(): HasMany
    {
        return $this->hasMany(LeadCall::class)->latest('call_date');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LeadMessage::class)->latest();
    }

    public function getIsClientOwnedAttribute(): bool
    {
        return $this->client_id !== null && $this->campaign_id === null;
    }

    public function scopeInbox($query)
    {
        return $query->where(function ($q) {
            $q->where('automation_status', LeadAutomationStatus::PENDING->value)
                ->orWhere('automation_status', LeadAutomationStatus::SKIPPED->value);
        })
            ->whereNull('intention_status')
            ->orderBy('created_at', 'desc');
    }

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
            // Exclude leads already finalized (those go to Sales Ready)
            ->where(function ($q) {
                $q->whereNull('intention_status')
                    ->orWhere('intention_status', '!=', LeadIntentionStatus::FINALIZED->value);
            })
            ->orderBy('next_action_at', 'asc')
            ->orderBy('updated_at', 'desc');
    }

    public function scopeSalesReady($query)
    {
        return $query->where('intention_status', LeadIntentionStatus::FINALIZED->value)
            ->where('status', '!=', LeadStatus::CLOSED->value)
            ->orderBy('intention_decided_at', 'desc');
    }

    public function scopeForClient($query, $clientId)
    {
        return $query->where(function ($q) use ($clientId) {
            $q->where('client_id', $clientId)
                ->orWhereHas('campaign', function ($subQuery) use ($clientId) {
                    $subQuery->where('client_id', $clientId);
                });
        });
    }

    public function scopeHasActivities($query)
    {
        return $query->whereHas('activities');
    }

    public function scopeHasCalls($query)
    {
        return $query->whereHas('calls');
    }

    public function scopeHasMessages($query)
    {
        return $query->whereHas('messages');
    }
}

