<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\DTOs\Lead\ResolvedOptionAction;
use App\Enums\CampaignActionType;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignOption;
use Illuminate\Support\Facades\Log;

/**
 * Resolves action configuration for lead options.
 *
 * Single Responsibility: Centralize the logic for resolving which action
 * and configuration to use for a lead's selected option.
 *
 * Supports multiple configuration sources:
 * 1. CampaignOption relation (preferred)
 * 2. Campaign JSON configuration (legacy)
 * 3. Direct trigger_action for DIRECT campaigns
 */
class OptionActionResolver
{
    /**
     * Resolve the action configuration for a lead option.
     *
     * @param Campaign $campaign The campaign
     * @param string|null $optionSelected The selected option key (null for DIRECT campaigns)
     * @return ResolvedOptionAction|null Resolved action config or null if no action
     */
    public function resolve(Campaign $campaign, ?string $optionSelected): ?ResolvedOptionAction
    {
        if ($campaign->isDirect()) {
            return $this->resolveDirectCampaign($campaign);
        }

        if (! $optionSelected) {
            Log::debug('No option selected for DYNAMIC campaign', [
                'campaign_id' => $campaign->id,
            ]);

            return null;
        }

        return $this->resolveDynamicCampaign($campaign, $optionSelected);
    }

    /**
     * Resolve action for DIRECT campaigns.
     * DIRECT campaigns execute the same action for all leads.
     */
    private function resolveDirectCampaign(Campaign $campaign): ?ResolvedOptionAction
    {
        if (! $campaign->hasDirectConfig()) {
            Log::warning('DIRECT campaign missing configuration', [
                'campaign_id' => $campaign->id,
            ]);

            return null;
        }

        $triggerAction = $campaign->getTriggerAction();

        if (! $triggerAction) {
            return null;
        }

        try {
            $actionType = CampaignActionType::from($triggerAction);
        } catch (\ValueError) {
            Log::error('Invalid trigger_action value', [
                'campaign_id' => $campaign->id,
                'trigger_action' => $triggerAction,
            ]);

            return null;
        }

        return new ResolvedOptionAction(
            actionType: $actionType,
            sourceId: $campaign->getSourceId(),
            templateId: $campaign->getTemplateId(),
            message: $campaign->getMessage(),
            agentId: $campaign->getAgentId(),
            delaySeconds: $campaign->getDelaySeconds(),
            enabled: true
        );
    }

    /**
     * Resolve action for DYNAMIC campaigns.
     * DYNAMIC campaigns select action based on option_selected.
     */
    private function resolveDynamicCampaign(Campaign $campaign, string $optionKey): ?ResolvedOptionAction
    {
        // Try CampaignOption relation first (preferred)
        $option = $campaign->getOption($optionKey);

        if ($option && $option->enabled) {
            return $this->resolveFromCampaignOption($option);
        }

        // Try JSON configuration (legacy)
        if ($campaign->hasDynamicConfig()) {
            return $this->resolveFromJsonConfig($campaign, $optionKey);
        }

        // Try fallback action
        $fallbackAction = $campaign->getFallbackAction();
        if ($fallbackAction) {
            Log::info('Using fallback_action for unmapped option', [
                'campaign_id' => $campaign->id,
                'option_key' => $optionKey,
                'fallback_action' => $fallbackAction,
            ]);

            try {
                $actionType = CampaignActionType::from($fallbackAction);

                return new ResolvedOptionAction(
                    actionType: $actionType,
                    enabled: true
                );
            } catch (\ValueError) {
                return null;
            }
        }

        Log::warning('No configuration found for option', [
            'campaign_id' => $campaign->id,
            'option_key' => $optionKey,
        ]);

        return null;
    }

    /**
     * Resolve from CampaignOption model.
     */
    private function resolveFromCampaignOption(CampaignOption $option): ?ResolvedOptionAction
    {
        if (! $option->action) {
            return null;
        }

        $actionType = $option->action instanceof CampaignActionType
            ? $option->action
            : CampaignActionType::tryFrom($option->action);

        if (! $actionType) {
            return null;
        }

        return new ResolvedOptionAction(
            actionType: $actionType,
            sourceId: $option->source_id,
            templateId: $option->template_id,
            message: $option->message,
            agentId: $option->agent_id ?? null,
            delaySeconds: $option->delay ?? 0,
            enabled: $option->enabled
        );
    }

    /**
     * Resolve from JSON configuration.
     */
    private function resolveFromJsonConfig(Campaign $campaign, string $optionKey): ?ResolvedOptionAction
    {
        $config = $campaign->getConfigForOption($optionKey);

        if (! $config) {
            return null;
        }

        $actionValue = $config['action'] ?? null;

        if (! $actionValue || $actionValue === 'do_nothing') {
            return null;
        }

        try {
            $actionType = CampaignActionType::from($actionValue);
        } catch (\ValueError) {
            Log::error('Invalid action in JSON config', [
                'campaign_id' => $campaign->id,
                'option_key' => $optionKey,
                'action_value' => $actionValue,
            ]);

            return null;
        }

        return new ResolvedOptionAction(
            actionType: $actionType,
            sourceId: $config['source_id'] ?? null,
            templateId: $config['template_id'] ?? null,
            message: $config['message'] ?? null,
            agentId: $config['agent_id'] ?? null,
            delaySeconds: $config['delay'] ?? 0,
            enabled: true
        );
    }
}

