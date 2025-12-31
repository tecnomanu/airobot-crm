<?php

declare(strict_types=1);

namespace App\DTOs\Lead;

use App\Enums\CampaignActionType;

/**
 * Data transfer object for resolved option action configuration.
 *
 * Provides a unified structure for action configuration regardless
 * of whether it came from CampaignOption, JSON config, or direct config.
 */
final readonly class ResolvedOptionAction
{
    public function __construct(
        public CampaignActionType $actionType,
        public ?string $sourceId = null,
        public ?string $templateId = null,
        public ?string $message = null,
        public ?string $agentId = null,
        public int $delaySeconds = 0,
        public bool $enabled = true
    ) {}

    /**
     * Check if this action requires a source.
     */
    public function requiresSource(): bool
    {
        return in_array($this->actionType, [
            CampaignActionType::WHATSAPP,
            CampaignActionType::WEBHOOK_CRM,
        ], true);
    }

    /**
     * Check if this is a skip/no-op action.
     */
    public function isSkip(): bool
    {
        return in_array($this->actionType, [
            CampaignActionType::SKIP,
            CampaignActionType::MANUAL_REVIEW,
        ], true);
    }

    /**
     * Check if this action has a delay configured.
     */
    public function hasDelay(): bool
    {
        return $this->delaySeconds > 0;
    }

    /**
     * Convert to array for logging/debugging.
     */
    public function toArray(): array
    {
        return [
            'action_type' => $this->actionType->value,
            'source_id' => $this->sourceId,
            'template_id' => $this->templateId,
            'message' => $this->message,
            'agent_id' => $this->agentId,
            'delay_seconds' => $this->delaySeconds,
            'enabled' => $this->enabled,
        ];
    }
}

