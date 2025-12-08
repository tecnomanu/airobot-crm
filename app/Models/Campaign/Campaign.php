<?php

namespace App\Models\Campaign;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\ExportRule;
use App\Models\Client\Client;
use App\Models\Integration\Source;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadCall;
use App\Models\Lead\LeadMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Campaign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'client_id',
        'description',
        'status',
        'slug',
        'auto_process_enabled',
        'country',
        'campaign_type',
        'default_action_config',
        'export_rule',
        'intention_interested_webhook_id',
        'intention_not_interested_webhook_id',
        'send_intention_interested_webhook',
        'send_intention_not_interested_webhook',
        'created_by',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'campaign_type' => CampaignType::class,
        'default_action_config' => 'array',
        'export_rule' => ExportRule::class,
        'auto_process_enabled' => 'boolean',
        'send_intention_interested_webhook' => 'boolean',
        'send_intention_not_interested_webhook' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(LeadCall::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LeadMessage::class);
    }

    public function whatsappTemplates(): HasMany
    {
        return $this->hasMany(CampaignWhatsappTemplate::class);
    }

    public function callAgent(): HasOne
    {
        return $this->hasOne(CampaignCallAgent::class);
    }

    public function whatsappAgent(): HasOne
    {
        return $this->hasOne(CampaignWhatsappAgent::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(CampaignOption::class);
    }

    public function intentionInterestedWebhook(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'intention_interested_webhook_id');
    }

    public function intentionNotInterestedWebhook(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'intention_not_interested_webhook_id');
    }

    public function getOption(string $optionKey): ?CampaignOption
    {
        return $this->options()->where('option_key', $optionKey)->first();
    }

    public function hasCallAgent(): bool
    {
        return $this->callAgent()->where('enabled', true)->exists();
    }

    public function hasWhatsappAgent(): bool
    {
        return $this->whatsappAgent()->where('enabled', true)->exists();
    }

    public function getEnabledCallAgent(): ?CampaignCallAgent
    {
        return $this->callAgent()->where('enabled', true)->first();
    }

    public function hasRetellCallAgent(): bool
    {
        $agent = $this->getEnabledCallAgent();

        return $agent && $agent->isRetell();
    }

    // =========================================================================
    // CAMPAIGN TYPE HELPERS
    // =========================================================================

    public function isInbound(): bool
    {
        return $this->campaign_type === CampaignType::INBOUND;
    }

    public function isOutbound(): bool
    {
        return $this->campaign_type === CampaignType::OUTBOUND;
    }

    /**
     * Whether this campaign requires option_selected to auto-process leads
     */
    public function requiresOptionSelection(): bool
    {
        return $this->campaign_type?->requiresOptionSelection() ?? true;
    }

    // =========================================================================
    // DEFAULT ACTION CONFIG ACCESSORS (for Outbound campaigns)
    // =========================================================================

    /**
     * Get the default action type for outbound campaigns
     */
    public function getDefaultAction(): ?string
    {
        return $this->default_action_config['action'] ?? null;
    }

    /**
     * Get the default agent ID for outbound campaigns (call agent)
     */
    public function getDefaultAgentId(): ?string
    {
        return $this->default_action_config['agent_id'] ?? null;
    }

    /**
     * Get the default source ID for outbound campaigns (WhatsApp source)
     */
    public function getDefaultSourceId(): ?string
    {
        return $this->default_action_config['source_id'] ?? null;
    }

    /**
     * Get the default template ID for outbound campaigns
     */
    public function getDefaultTemplateId(): ?string
    {
        return $this->default_action_config['template_id'] ?? null;
    }

    /**
     * Get the default message for outbound campaigns
     */
    public function getDefaultMessage(): ?string
    {
        return $this->default_action_config['message'] ?? null;
    }

    /**
     * Check if campaign has valid default action configuration
     */
    public function hasDefaultActionConfig(): bool
    {
        return ! empty($this->default_action_config)
            && ! empty($this->getDefaultAction());
    }
}
