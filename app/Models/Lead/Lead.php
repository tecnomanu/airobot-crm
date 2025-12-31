<?php

namespace App\Models\Lead;

use App\Enums\LeadAutomationStatus;
use App\Enums\LeadCloseReason;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStage;
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

    protected static function newFactory()
    {
        return \Database\Factories\LeadFactory::new();
    }

    protected $fillable = [
        'client_id',
        'phone',
        'name',
        'email',
        'city',
        'country',
        'option_selected',
        'campaign_id',
        // Stage - single source of truth for UI
        'stage',
        // Legacy status (kept for backwards compatibility)
        'status',
        'source',
        'sent_at',
        // Intention tracking
        'intention',
        'intention_origin',
        'intention_status',
        'intention_decided_at',
        'exported_at',
        // Notes and tags
        'notes',
        'tags',
        'manual_classification',
        'decision_notes',
        'ai_agent_active',
        // Close fields
        'closed_at',
        'close_reason',
        'close_notes',
        // Legacy webhook tracking
        'webhook_sent',
        'webhook_result',
        'intention_webhook_sent',
        'intention_webhook_sent_at',
        'intention_webhook_response',
        'intention_webhook_status',
        // Automation
        'automation_status',
        'next_action_at',
        'last_automation_run_at',
        'automation_attempts',
        'automation_error',
        // Assignment
        'assigned_to',
        'assigned_at',
        'assignment_error',
        'created_by',
    ];

    protected $casts = [
        'stage' => LeadStage::class,
        'status' => LeadStatus::class,
        'automation_status' => LeadAutomationStatus::class,
        'intention_origin' => LeadIntentionOrigin::class,
        'intention_status' => LeadIntentionStatus::class,
        'close_reason' => LeadCloseReason::class,
        'sent_at' => 'datetime',
        'intention_decided_at' => 'datetime',
        'exported_at' => 'datetime',
        'closed_at' => 'datetime',
        'next_action_at' => 'datetime',
        'last_automation_run_at' => 'datetime',
        'webhook_sent' => 'boolean',
        'intention_webhook_sent' => 'boolean',
        'intention_webhook_sent_at' => 'datetime',
        'tags' => 'array',
        'ai_agent_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    protected $attributes = [
        'stage' => 'inbox',
        'automation_status' => 'pending',
    ];

    // Relation constants
    public const RELATION_CAMPAIGN = 'campaign';
    public const RELATION_CLIENT = 'client';
    public const RELATION_CREATOR = 'creator';
    public const RELATION_ASSIGNEE = 'assignee';
    public const RELATION_ACTIVITIES = 'activities';
    public const RELATION_CALLS = 'calls';
    public const RELATION_MESSAGES = 'messages';
    public const RELATION_DISPATCH_ATTEMPTS = 'dispatchAttempts';

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    public function dispatchAttempts(): HasMany
    {
        return $this->hasMany(LeadDispatchAttempt::class)->latest();
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

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

    public function getIsClientOwnedAttribute(): bool
    {
        return $this->client_id !== null && $this->campaign_id === null;
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->stage === LeadStage::CLOSED;
    }

    public function getCanStartAutomationAttribute(): bool
    {
        return $this->stage?->canStartAutomation() ?? true
            && $this->automation_status?->canStart() ?? true;
    }

    // ==========================================
    // SCOPES - Stage-based (primary)
    // ==========================================

    /**
     * Filter by stage (primary method for UI tabs).
     */
    public function scopeByStage($query, LeadStage $stage)
    {
        return $query->where('stage', $stage->value);
    }

    /**
     * Inbox: new leads pending processing.
     */
    public function scopeInbox($query)
    {
        return $query->where('stage', LeadStage::INBOX->value)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Qualifying: leads in automation/contact flow.
     */
    public function scopeQualifying($query)
    {
        return $query->where('stage', LeadStage::QUALIFYING->value)
            ->orderBy('next_action_at', 'asc')
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Sales Ready: qualified leads for human handoff.
     */
    public function scopeSalesReady($query)
    {
        return $query->where('stage', LeadStage::SALES_READY->value)
            ->orderBy('intention_decided_at', 'desc')
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Closed: completed leads.
     */
    public function scopeClosed($query)
    {
        return $query->where('stage', LeadStage::CLOSED->value)
            ->orderBy('closed_at', 'desc')
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Errors: leads with automation errors (any non-closed stage).
     */
    public function scopeWithErrors($query)
    {
        return $query->where('stage', '!=', LeadStage::CLOSED->value)
            ->where(function ($q) {
                $q->where('automation_status', LeadAutomationStatus::FAILED->value)
                    ->orWhereNotNull('automation_error');
            })
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Active: not closed.
     */
    public function scopeActive($query)
    {
        return $query->where('stage', '!=', LeadStage::CLOSED->value);
    }

    // ==========================================
    // SCOPES - Legacy (for backwards compatibility)
    // ==========================================

    /**
     * @deprecated Use scopeQualifying() instead
     */
    public function scopeActivePipeline($query)
    {
        return $this->scopeQualifying($query);
    }

    // ==========================================
    // SCOPES - Filters
    // ==========================================

    public function scopeForClient($query, $clientId)
    {
        return $query->where(function ($q) use ($clientId) {
            $q->where('client_id', $clientId)
                ->orWhereHas('campaign', function ($subQuery) use ($clientId) {
                    $subQuery->where('client_id', $clientId);
                });
        });
    }

    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeByCloseReason($query, LeadCloseReason $reason)
    {
        return $query->where('close_reason', $reason->value);
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

    public function scopePendingDispatch($query)
    {
        return $query->whereDoesntHave('dispatchAttempts', function ($q) {
            $q->where('status', 'success');
        });
    }
}
