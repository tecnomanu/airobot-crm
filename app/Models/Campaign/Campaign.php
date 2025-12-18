<?php

namespace App\Models\Campaign;

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
        'country',
        'strategy_type',
        'configuration',
        'export_rule',
        'intention_interested_webhook_id',
        'intention_not_interested_webhook_id',
        'send_intention_interested_webhook',
        'send_intention_not_interested_webhook',
        'google_integration_id',
        'google_spreadsheet_id',
        'google_sheet_name',
        'intention_not_interested_google_spreadsheet_id',
        'intention_not_interested_google_sheet_name',
        'created_by',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'strategy_type' => CampaignStrategy::class,
        'configuration' => 'array',
        'export_rule' => ExportRule::class,
        'auto_process_enabled' => 'boolean',
        'send_intention_interested_webhook' => 'boolean',
        'send_intention_not_interested_webhook' => 'boolean',
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

    public function intentionInterestedWebhook(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'intention_interested_webhook_id');
    }

    public function intentionNotInterestedWebhook(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'intention_not_interested_webhook_id');
    }

    // =========================================================================
    // LEGACY SUPPORT - CampaignOption relation (for dynamic campaigns)
    // =========================================================================

    public function getOption(string $optionKey): ?CampaignOption
    {
        return $this->options()->where('option_key', $optionKey)->first();
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
    // CONFIGURATION ACCESSORS - DIRECT STRATEGY
    // Direct config: { trigger_action, agent_id, template_id, source_id, message, delay_seconds }
    // =========================================================================

    /**
     * Get the trigger action for direct campaigns (call, whatsapp, webhook, etc.)
     */
    public function getTriggerAction(): ?string
    {
        return $this->configuration['trigger_action'] ?? null;
    }

    /**
     * Get the agent ID for direct campaigns (call agent)
     */
    public function getAgentId(): ?string
    {
        return $this->configuration['agent_id'] ?? null;
    }

    /**
     * Get the source ID for direct campaigns (WhatsApp source)
     */
    public function getSourceId(): ?string
    {
        return $this->configuration['source_id'] ?? null;
    }

    /**
     * Get the template ID for direct campaigns
     */
    public function getTemplateId(): ?string
    {
        return $this->configuration['template_id'] ?? null;
    }

    /**
     * Get the message for direct campaigns
     */
    public function getMessage(): ?string
    {
        return $this->configuration['message'] ?? null;
    }

    /**
     * Get the delay in seconds before triggering action (default 0)
     */
    public function getDelaySeconds(): int
    {
        return (int) ($this->configuration['delay_seconds'] ?? 0);
    }

    /**
     * Check if campaign has valid direct configuration
     */
    public function hasDirectConfig(): bool
    {
        return $this->isDirect()
            && ! empty($this->configuration)
            && ! empty($this->getTriggerAction());
    }

    // =========================================================================
    // CONFIGURATION ACCESSORS - DYNAMIC STRATEGY
    // Dynamic config: { fallback_action, mapping: { "1": { action, agent_id }, ... } }
    // =========================================================================

    /**
     * Get the mapping of options to actions
     * @return array<string, array{action: string, agent_id?: string, template_id?: string, source_id?: string, message?: string}>
     */
    public function getOptionMapping(): array
    {
        return $this->configuration['mapping'] ?? [];
    }

    /**
     * Get the fallback action when option is not found in mapping
     */
    public function getFallbackAction(): ?string
    {
        return $this->configuration['fallback_action'] ?? null;
    }

    /**
     * Get configuration for a specific option from mapping
     * @return array{action: string, agent_id?: string, template_id?: string, source_id?: string, message?: string}|null
     */
    public function getConfigForOption(string $optionKey): ?array
    {
        $mapping = $this->getOptionMapping();

        return $mapping[$optionKey] ?? null;
    }

    /**
     * Get the action type for a specific option
     */
    public function getActionForOption(string $optionKey): ?string
    {
        $config = $this->getConfigForOption($optionKey);

        return $config['action'] ?? $this->getFallbackAction();
    }

    /**
     * Get the agent ID for a specific option
     */
    public function getAgentForOption(string $optionKey): ?string
    {
        $config = $this->getConfigForOption($optionKey);

        return $config['agent_id'] ?? null;
    }

    /**
     * Get the template ID for a specific option
     */
    public function getTemplateForOption(string $optionKey): ?string
    {
        $config = $this->getConfigForOption($optionKey);

        return $config['template_id'] ?? null;
    }

    /**
     * Get the source ID for a specific option
     */
    public function getSourceForOption(string $optionKey): ?string
    {
        $config = $this->getConfigForOption($optionKey);

        return $config['source_id'] ?? null;
    }

    /**
     * Get the message for a specific option
     */
    public function getMessageForOption(string $optionKey): ?string
    {
        $config = $this->getConfigForOption($optionKey);

        return $config['message'] ?? null;
    }

    /**
     * Check if option exists in mapping
     */
    public function hasOptionInMapping(string $optionKey): bool
    {
        return isset($this->getOptionMapping()[$optionKey]);
    }

    /**
     * Check if campaign has valid dynamic configuration
     */
    public function hasDynamicConfig(): bool
    {
        return $this->isDynamic()
            && ! empty($this->configuration)
            && ! empty($this->getOptionMapping());
    }

    // =========================================================================
    // UNIFIED CONFIGURATION HELPERS
    // =========================================================================

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

    /**
     * Get full configuration array
     */
    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }
}
