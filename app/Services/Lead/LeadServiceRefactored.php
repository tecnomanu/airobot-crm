<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Contracts\WhatsAppSenderInterface;
use App\DTOs\Lead\ResolvedOptionAction;
use App\Enums\CampaignActionType;
use App\Enums\LeadAutomationStatus;
use App\Enums\LeadIntention;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStage;
use App\Enums\LeadStatus;
use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Enums\SourceStatus;
use App\Events\Lead\LeadCreated;
use App\Events\Lead\LeadStageChanged;
use App\Exceptions\Business\ConfigurationException;
use App\Models\Campaign\Campaign;
use App\Models\Integration\Source;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadMessage;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Core Lead Service - Orchestrates lead operations.
 *
 * This service delegates to specialized services:
 * - LeadQueryService: Read-only queries
 * - LeadIngestionService: Webhook/form ingestion
 * - LeadWhatsAppService: WhatsApp-specific logic
 * - OptionActionResolver: Option configuration resolution
 *
 * Responsibilities retained:
 * - CRUD operations with events
 * - Automation orchestration
 * - Action execution
 */
class LeadServiceRefactored
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly LeadQueryService $queryService,
        private readonly LeadIngestionService $ingestionService,
        private readonly LeadWhatsAppService $whatsappService,
        private readonly OptionActionResolver $actionResolver,
        private readonly WhatsAppSenderInterface $whatsappSender,
        private readonly LeadOptionProcessorService $optionProcessor
    ) {}

    // =========================================================================
    // QUERY OPERATIONS (Delegated to LeadQueryService)
    // =========================================================================

    public function getLeads(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryService->getLeads($filters, $perPage);
    }

    public function getLeadsForManager(string $tab, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryService->getLeadsForManager($tab, $filters, $perPage);
    }

    public function getTabCounts(array $filters = []): array
    {
        return $this->queryService->getTabCounts($filters);
    }

    public function getLeadById(string $id): ?Lead
    {
        return $this->queryService->getLeadById($id);
    }

    public function getLeadsByCampaign(string $campaignId, ?string $status = null)
    {
        return $this->queryService->getLeadsByCampaign($campaignId, $status);
    }

    public function getStatusCountByCampaign(string $campaignId): array
    {
        return $this->queryService->getStatusCountByCampaign($campaignId);
    }

    public function getRecentLeads(int $limit = 10)
    {
        return $this->queryService->getRecentLeads($limit);
    }

    public function getPendingWebhookLeads()
    {
        return $this->queryService->getPendingWebhookLeads();
    }

    public function getPendingAutomation(array $filters = []): LengthAwarePaginator
    {
        return $this->queryService->getPendingAutomation($filters);
    }

    // =========================================================================
    // INGESTION OPERATIONS (Delegated to LeadIngestionService)
    // =========================================================================

    public function createLead(array $data): Lead
    {
        $lead = $this->ingestionService->createLead($data);

        // Trigger automation after creation
        $this->autoProcessLeadIfEnabled($lead);

        return $lead;
    }

    public function processIncomingWebhookLead(array $leadData): Lead
    {
        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        // Trigger automation after ingestion
        $this->autoProcessLeadIfEnabled($lead);

        return $lead;
    }

    // =========================================================================
    // WHATSAPP OPERATIONS (Delegated to LeadWhatsAppService)
    // =========================================================================

    public function findOrCreateFromWhatsApp(
        string $phone,
        ?array $whatsappData = null,
        ?string $defaultCampaignId = null
    ): Lead {
        return $this->whatsappService->findOrCreateFromWhatsApp(
            $phone,
            $whatsappData,
            null,
            $defaultCampaignId
        );
    }

    public function updateIntentionFromMessage(Lead $lead, string $messageContent): void
    {
        $this->whatsappService->updateIntentionFromMessage($lead, $messageContent);
    }

    public function updateContactInfoFromWhatsApp(Lead $lead, array $whatsappData): void
    {
        $this->whatsappService->updateContactInfoIfBetter($lead, $whatsappData);
    }

    // =========================================================================
    // CRUD OPERATIONS (Core responsibility)
    // =========================================================================

    public function updateLead(string $id, array $data): Lead
    {
        $lead = $this->leadRepository->findById($id);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead not found');
        }

        $previousStage = $this->computeStage($lead);

        $lead = $this->leadRepository->update($lead, $data);

        $newStage = $this->computeStage($lead);

        // Emit stage change event if stage changed
        if ($previousStage !== $newStage) {
            LeadStageChanged::dispatch($lead, $previousStage, $newStage);
        }

        return $lead;
    }

    public function deleteLead(string $id): bool
    {
        $lead = $this->leadRepository->findById($id, ['campaign']);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead not found');
        }

        // Note: Broadcasting for delete is handled via LeadDeleted event (to be created if needed)
        return $this->leadRepository->delete($lead);
    }

    public function markWebhookSent(string $leadId, string $result): Lead
    {
        $lead = $this->leadRepository->findById($leadId);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead not found');
        }

        return $this->leadRepository->update($lead, [
            'webhook_sent' => true,
            'webhook_result' => $result,
            'sent_at' => now(),
        ]);
    }

    // =========================================================================
    // AUTOMATION ORCHESTRATION
    // =========================================================================

    /**
     * Process lead automation if campaign has auto_process_enabled.
     */
    public function autoProcessLeadIfEnabled(Lead $lead): void
    {
        $campaign = $lead->campaign;

        if (! $campaign || ! $campaign->auto_process_enabled) {
            Log::info('Auto-process disabled for campaign', [
                'lead_id' => $lead->id,
                'campaign_id' => $campaign?->id,
            ]);

            return;
        }

        $resolvedAction = $this->actionResolver->resolve($campaign, $lead->option_selected);

        if (! $resolvedAction) {
            Log::info('No action resolved for lead', [
                'lead_id' => $lead->id,
                'option_selected' => $lead->option_selected,
            ]);

            return;
        }

        $this->executeAutomation($lead, $campaign, $resolvedAction);
    }

    /**
     * Execute automation action for a lead.
     */
    protected function executeAutomation(Lead $lead, Campaign $campaign, ResolvedOptionAction $action): void
    {
        try {
            Log::info('Executing lead automation', [
                'lead_id' => $lead->id,
                'campaign_id' => $campaign->id,
                'action_type' => $action->actionType->value,
            ]);

            // Update status to processing
            $this->leadRepository->update($lead, [
                'automation_status' => LeadAutomationStatus::PROCESSING,
                'last_automation_run_at' => now(),
                'automation_attempts' => $lead->automation_attempts + 1,
            ]);

            // Execute the action
            $this->executeAction($lead, $campaign, $action);

            // Determine final status
            $finalStatus = $action->isSkip()
                ? LeadAutomationStatus::SKIPPED
                : LeadAutomationStatus::COMPLETED;

            $this->leadRepository->update($lead, [
                'automation_status' => $finalStatus,
                'automation_error' => null,
            ]);

            Log::info('Lead automation completed', [
                'lead_id' => $lead->id,
                'automation_status' => $finalStatus->value,
            ]);
        } catch (ConfigurationException $e) {
            $this->leadRepository->update($lead, [
                'automation_status' => LeadAutomationStatus::SKIPPED,
                'automation_error' => $e->getMessage(),
            ]);

            Log::warning('Lead automation skipped due to configuration error', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->leadRepository->update($lead, [
                'automation_status' => LeadAutomationStatus::FAILED,
                'automation_error' => $e->getMessage(),
            ]);

            Log::error('Lead automation failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Execute specific action type.
     */
    protected function executeAction(Lead $lead, Campaign $campaign, ResolvedOptionAction $action): void
    {
        match ($action->actionType) {
            CampaignActionType::CALL_AI => $this->executeCallAction($lead, $action),
            CampaignActionType::WHATSAPP => $this->executeWhatsAppAction($lead, $campaign, $action),
            CampaignActionType::WEBHOOK_CRM => $this->executeWebhookAction($lead, $action),
            CampaignActionType::MANUAL_REVIEW => $this->executeManualReviewAction($lead),
            CampaignActionType::SKIP => $this->executeSkipAction($lead),
        };
    }

    protected function executeCallAction(Lead $lead, ResolvedOptionAction $action): void
    {
        Log::info('Executing call action', [
            'lead_id' => $lead->id,
            'agent_id' => $action->agentId,
        ]);

        // TODO: Implement call dispatch
        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'last_automation_run_at' => now(),
        ]);
    }

    protected function executeWhatsAppAction(Lead $lead, Campaign $campaign, ResolvedOptionAction $action): void
    {
        if (! $action->sourceId) {
            throw new ConfigurationException('WhatsApp action requires source_id');
        }

        $source = Source::find($action->sourceId);

        if (! $source) {
            throw new ConfigurationException("Source '{$action->sourceId}' not found");
        }

        if ($source->status !== SourceStatus::ACTIVE) {
            throw new ConfigurationException("Source '{$source->name}' is not active");
        }

        $messageBody = $this->buildMessageBody($action->message, $lead);

        if (empty($messageBody)) {
            $messageBody = "Hola {$lead->name}, gracias por tu interés. Un asesor se contactará contigo pronto.";
        }

        $result = $this->whatsappSender->sendMessage($source, $lead, $messageBody);

        Log::info('WhatsApp message sent', [
            'lead_id' => $lead->id,
            'source_id' => $source->id,
        ]);

        LeadMessage::create([
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'channel' => MessageChannel::WHATSAPP,
            'direction' => MessageDirection::OUTBOUND,
            'status' => MessageStatus::SENT,
            'content' => $messageBody,
            'metadata' => [
                'source_id' => $source->id,
                'automation' => true,
                'result' => $result,
            ],
            'phone' => $lead->phone,
        ]);

        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'intention_status' => LeadIntentionStatus::PENDING,
            'intention_origin' => LeadIntentionOrigin::WHATSAPP,
        ]);
    }

    protected function executeWebhookAction(Lead $lead, ResolvedOptionAction $action): void
    {
        Log::info('Executing webhook action', ['lead_id' => $lead->id]);

        // TODO: Implement webhook dispatch
        $lead->update([
            'status' => LeadStatus::IN_PROGRESS,
            'last_automation_run_at' => now(),
        ]);
    }

    protected function executeManualReviewAction(Lead $lead): void
    {
        Log::info('Lead marked for manual review', ['lead_id' => $lead->id]);

        $lead->update([
            'status' => LeadStatus::PENDING,
            'last_automation_run_at' => now(),
        ]);
    }

    protected function executeSkipAction(Lead $lead): void
    {
        Log::info('Executing skip action - lead goes to Sales Ready', [
            'lead_id' => $lead->id,
        ]);

        $lead->update([
            'status' => LeadStatus::CONTACTED,
            'intention' => LeadIntention::INTERESTED->value,
            'intention_status' => LeadIntentionStatus::FINALIZED,
            'intention_decided_at' => now(),
            'intention_origin' => LeadIntentionOrigin::IVR,
        ]);
    }

    protected function buildMessageBody(?string $template, Lead $lead): string
    {
        if (empty($template)) {
            return '';
        }

        $replacements = [
            '{{name}}' => $lead->name ?? 'Cliente',
            '{{phone}}' => $lead->phone ?? '',
            '{{city}}' => $lead->city ?? '',
            '{{campaign}}' => $lead->campaign?->name ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    // =========================================================================
    // RETRY OPERATIONS
    // =========================================================================

    public function retryAutomation(string $id): Lead
    {
        $lead = $this->leadRepository->findById($id, ['campaign.options']);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead not found');
        }

        // Reset automation state
        $this->leadRepository->update($lead, [
            'automation_status' => LeadAutomationStatus::PENDING,
            'automation_error' => null,
        ]);

        $lead->refresh();

        // Re-process
        $this->autoProcessLeadIfEnabled($lead);

        $lead->refresh();

        if ($lead->automation_status === LeadAutomationStatus::FAILED) {
            throw new \Exception('Automation retry failed: ' . ($lead->automation_error ?? 'Unknown error'));
        }

        Log::info('Automation retry successful', [
            'lead_id' => $lead->id,
            'automation_status' => $lead->automation_status->value,
        ]);

        return $lead->fresh();
    }

    public function retryAutomationBatch(array $filters = []): array
    {
        $leads = $this->queryService->getFailedAutomation($filters);

        $results = [
            'total' => $leads->count(),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($leads as $lead) {
            try {
                $this->retryAutomation($lead->id);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Batch automation retry completed', $results);

        return $results;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function computeStage(Lead $lead): LeadStage
    {
        return $lead->stage ?? LeadStage::INBOX;
    }
}

