<?php

namespace App\Models\Campaign;

use App\Enums\CampaignActionType;
use App\Enums\CampaignStatus;
use App\Enums\CampaignStrategy;
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

    protected static function newFactory()
    {
        return \Database\Factories\CampaignFactory::new();
    }

    protected $fillable = [
        'name',
        'client_id',
        'description',
        'status',
        'slug',
        'auto_process_enabled',
        'use_client_call_defaults',
        'use_client_whatsapp_defaults',
        'no_response_action_enabled',
        'no_response_max_attempts',
        'no_response_timeout_hours',
        'country',
        'strategy_type',
        'export_rule',
        'created_by',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'strategy_type' => CampaignStrategy::class,
        'export_rule' => ExportRule::class,
        'auto_process_enabled' => 'boolean',
        'use_client_call_defaults' => 'boolean',
        'use_client_whatsapp_defaults' => 'boolean',
        'no_response_action_enabled' => 'boolean',
        'no_response_max_attempts' => 'integer',
        'no_response_timeout_hours' => 'integer',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

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

    public function intentionActions(): HasMany
    {
        return $this->hasMany(CampaignIntentionAction::class);
    }

    public function agent(): HasOne
    {
        return $this->hasOne(\App\Models\AI\CampaignAgent::class);
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(CampaignAssignee::class);
    }

    public function activeAssignees(): HasMany
    {
        return $this->hasMany(CampaignAssignee::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function assignmentCursor(): HasOne
    {
        return $this->hasOne(CampaignAssignmentCursor::class);
    }


    // =========================================================================
    // LEGACY SUPPORT - CampaignOption relation
    // =========================================================================

    /**
     * Get option by key
     */
    public function getOption(string $optionKey): ?CampaignOption
    {
        return $this->options()->where('option_key', $optionKey)->first();
    }

    /**
     * Get option for direct campaigns (always option_key='0')
     */
    public function getDirectOption(): ?CampaignOption
    {
        return $this->getOption('0');
    }

    /**
     * Get all enabled options
     */
    public function getEnabledOptions()
    {
        return $this->options()->where('enabled', true)->get();
    }

    // =========================================================================
    // INTENTION ACTION HELPERS
    // =========================================================================

    /**
     * Get intention action for interested leads
     */
    public function getInterestedAction(): ?CampaignIntentionAction
    {
        return $this->intentionActions()->where('intention_type', 'interested')->first();
    }

    /**
     * Get intention action for not interested leads
     */
    public function getNotInterestedAction(): ?CampaignIntentionAction
    {
        return $this->intentionActions()->where('intention_type', 'not_interested')->first();
    }

    /**
     * Check if campaign should send interested webhook
     */
    public function shouldSendInterestedWebhook(): bool
    {
        $action = $this->getInterestedAction();
        return $action && $action->isWebhook() && $action->enabled;
    }

    /**
     * Check if campaign should send not interested webhook
     */
    public function shouldSendNotInterestedWebhook(): bool
    {
        $action = $this->getNotInterestedAction();
        return $action && $action->isWebhook() && $action->enabled;
    }

    /**
     * Check if campaign should export interested to spreadsheet
     */
    public function shouldExportInterestedToSpreadsheet(): bool
    {
        $action = $this->getInterestedAction();
        return $action && $action->isSpreadsheet() && $action->enabled;
    }

    /**
     * Check if campaign should export not interested to spreadsheet
     */
    public function shouldExportNotInterestedToSpreadsheet(): bool
    {
        $action = $this->getNotInterestedAction();
        return $action && $action->isSpreadsheet() && $action->enabled;
    }

    // =========================================================================
    // CALL/WHATSAPP AGENT HELPERS
    // =========================================================================

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

    public function hasAIAgent(): bool
    {
        return $this->agent()->where('enabled', true)->exists();
    }

    public function getEnabledAIAgent(): ?\App\Models\AI\CampaignAgent
    {
        return $this->agent()->where('enabled', true)->first();
    }

    // =========================================================================
    // RESOLVED AGENT HELPERS (Campaign → Client fallback)
    // =========================================================================

    /**
     * Get the effective WhatsApp source (campaign-specific or client default).
     */
    public function getResolvedWhatsappSource(): ?Source
    {
        // 1. Try campaign's own WhatsApp agent source
        if ($this->whatsappAgent && $this->whatsappAgent->enabled && $this->whatsappAgent->source) {
            return $this->whatsappAgent->source;
        }

        // 2. Fallback to client default if flag is enabled
        if ($this->use_client_whatsapp_defaults && $this->client?->defaultWhatsappSource) {
            return $this->client->defaultWhatsappSource;
        }

        return null;
    }

    /**
     * Get the effective call agent (campaign-specific or client default).
     */
    public function getResolvedCallAgent(): ?\App\Models\RetellAgent
    {
        // 1. Try campaign's own call agent
        $campaignCallAgent = $this->getEnabledCallAgent();
        if ($campaignCallAgent && $campaignCallAgent->retell_agent_id) {
            return $campaignCallAgent->retellAgent;
        }

        // 2. Fallback to client default if flag is enabled
        if ($this->use_client_call_defaults && $this->client?->defaultCallAgent) {
            return $this->client->defaultCallAgent;
        }

        return null;
    }

    /**
     * Check if campaign has a resolved WhatsApp source (own or client default).
     */
    public function hasResolvedWhatsappSource(): bool
    {
        return $this->getResolvedWhatsappSource() !== null;
    }

    /**
     * Check if campaign has a resolved call agent (own or client default).
     */
    public function hasResolvedCallAgent(): bool
    {
        return $this->getResolvedCallAgent() !== null;
    }

    /**
     * Check if campaign is using client defaults for WhatsApp.
     */
    public function isUsingClientWhatsappDefaults(): bool
    {
        // Has no own config but has client default
        $hasOwnSource = $this->whatsappAgent && $this->whatsappAgent->enabled && $this->whatsappAgent->source_id;

        return !$hasOwnSource
            && $this->use_client_whatsapp_defaults
            && $this->client?->default_whatsapp_source_id !== null;
    }

    /**
     * Check if campaign is using client defaults for call.
     */
    public function isUsingClientCallDefaults(): bool
    {
        $campaignCallAgent = $this->getEnabledCallAgent();
        $hasOwnAgent = $campaignCallAgent && $campaignCallAgent->retell_agent_id;

        return !$hasOwnAgent
            && $this->use_client_call_defaults
            && $this->client?->default_call_agent_id !== null;
    }

    // =========================================================================
    // ACTIONS USED HELPERS (for conditional validation)
    // =========================================================================

    /**
     * Get all unique action types used by this campaign.
     * For direct campaigns: single action from option_key='0'
     * For dynamic campaigns: all actions from enabled options
     */
    public function getUsedActionTypes(): array
    {
        $actions = [];

        if ($this->isDirect()) {
            $option = $this->getDirectOption();
            if ($option && $option->action) {
                $actions[] = $option->action;
            }
        } else {
            // Dynamic: collect all enabled options' actions
            foreach ($this->getEnabledOptions() as $option) {
                if ($option->action) {
                    $actions[] = $option->action;
                }
            }
        }

        return array_unique($actions, SORT_REGULAR);
    }

    /**
     * Check if campaign uses WhatsApp action (anywhere).
     */
    public function usesWhatsappAction(): bool
    {
        foreach ($this->getUsedActionTypes() as $action) {
            if ($action === CampaignActionType::WHATSAPP) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if campaign uses Call AI action (anywhere).
     */
    public function usesCallAction(): bool
    {
        foreach ($this->getUsedActionTypes() as $action) {
            if ($action === CampaignActionType::CALL_AI) {
                return true;
            }
        }

        return false;
    }


    // =========================================================================
    // STRATEGY TYPE HELPERS
    // =========================================================================

    public function isDirect(): bool
    {
        return $this->strategy_type === CampaignStrategy::DIRECT;
    }

    public function isDynamic(): bool
    {
        return $this->strategy_type === CampaignStrategy::DYNAMIC;
    }

    /**
     * Whether this campaign requires option_selected to auto-process leads
     */
    public function requiresOptionSelection(): bool
    {
        return $this->strategy_type?->requiresOptionSelection() ?? true;
    }

    // =========================================================================
    // CONFIGURATION ACCESSORS - Using CampaignOption
    // =========================================================================

    /**
     * Get the trigger action for direct campaigns
     * For direct campaigns, we use option_key='0'
     */
    public function getTriggerAction(): ?string
    {
        if (!$this->isDirect()) {
            return null;
        }

        $option = $this->getDirectOption();
        return $option?->action?->value ?? null;
    }

    /**
     * Get the source ID for direct campaigns
     */
    public function getSourceId(): ?string
    {
        if (!$this->isDirect()) {
            return null;
        }

        $option = $this->getDirectOption();
        return $option?->source_id;
    }

    /**
     * Get the template ID for direct campaigns
     */
    public function getTemplateId(): ?string
    {
        if (!$this->isDirect()) {
            return null;
        }

        $option = $this->getDirectOption();
        return $option?->template_id;
    }

    /**
     * Get the message for direct campaigns
     */
    public function getMessage(): ?string
    {
        if (!$this->isDirect()) {
            return null;
        }

        $option = $this->getDirectOption();
        return $option?->message;
    }

    /**
     * Get the delay in seconds before triggering action
     */
    public function getDelaySeconds(): int
    {
        if (!$this->isDirect()) {
            return 0;
        }

        $option = $this->getDirectOption();
        return $option?->delay ?? 0;
    }

    /**
     * Check if campaign has valid direct configuration
     */
    public function hasDirectConfig(): bool
    {
        return $this->isDirect()
            && $this->getDirectOption() !== null
            && $this->getDirectOption()->enabled;
    }

    /**
     * Get configuration for a specific option from options table
     */
    public function getConfigForOption(string $optionKey): ?CampaignOption
    {
        return $this->getOption($optionKey);
    }

    /**
     * Get the action type for a specific option
     */
    public function getActionForOption(string $optionKey): ?string
    {
        $option = $this->getOption($optionKey);
        return $option?->action?->value ?? null;
    }

    /**
     * Get the source ID for a specific option
     */
    public function getSourceForOption(string $optionKey): ?string
    {
        $option = $this->getOption($optionKey);
        return $option?->source_id;
    }

    /**
     * Get the template ID for a specific option
     */
    public function getTemplateForOption(string $optionKey): ?string
    {
        $option = $this->getOption($optionKey);
        return $option?->template_id;
    }

    /**
     * Get the message for a specific option
     */
    public function getMessageForOption(string $optionKey): ?string
    {
        $option = $this->getOption($optionKey);
        return $option?->message;
    }

    /**
     * Check if option exists
     */
    public function hasOptionInMapping(string $optionKey): bool
    {
        return $this->options()->where('option_key', $optionKey)->exists();
    }

    /**
     * Check if campaign has valid dynamic configuration
     */
    public function hasDynamicConfig(): bool
    {
        return $this->isDynamic()
            && $this->options()->where('enabled', true)->exists();
    }

    /**
     * Check if campaign has valid configuration for its strategy type
     */
    public function hasValidConfiguration(): bool
    {
        if ($this->isDirect()) {
            return $this->hasDirectConfig();
        }

        if ($this->isDynamic()) {
            return $this->hasDynamicConfig();
        }

        return false;
    }
}
