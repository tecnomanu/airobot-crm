<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Enums\LeadSource;
use App\Enums\LeadStage;
use App\Enums\LeadStatus;
use App\Events\Lead\LeadCreated;
use App\Events\Lead\LeadStageChanged;
use App\Helpers\PhoneHelper;
use App\Models\Campaign\Campaign;
use App\Models\Lead\Lead;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for ingesting leads from external sources (webhooks, forms, etc).
 *
 * Single Responsibility: Process incoming lead data and create/update leads.
 * Emits domain events for side effects (broadcasting, automation).
 */
class LeadIngestionService
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {}

    /**
     * Process an incoming lead from an external webhook.
     *
     * @param array $leadData Raw lead data from webhook
     * @throws \InvalidArgumentException If phone is invalid
     * @throws \Exception If no campaign can be associated
     */
    public function processIncomingWebhookLead(array $leadData): Lead
    {
        return DB::transaction(function () use ($leadData) {
            $campaign = $this->findCampaignForLead($leadData);

            $phone = $this->normalizePhone($leadData['phone'], $campaign);

            if (! PhoneHelper::isValid($phone)) {
                throw new \InvalidArgumentException("Invalid phone number: {$leadData['phone']}");
            }

            if (! $campaign) {
                Log::warning('No campaign found for lead', [
                    'phone' => $phone,
                    'campaign_slug' => $leadData['slug'] ?? $leadData['campaign'] ?? null,
                ]);
                throw new \Exception('Could not associate lead with any active campaign.');
            }

            $existingLead = $this->leadRepository->findByPhoneAndCampaign($phone, $campaign->id);
            $isNew = ! $existingLead;

            $data = $this->buildLeadData($leadData, $campaign, $phone);

            if ($existingLead) {
                $lead = $this->updateExistingLead($existingLead, $data);
            } else {
                $lead = $this->createNewLead($data, $leadData);
            }

            $this->dispatchEvents($lead, $isNew);

            return $lead;
        });
    }

    /**
     * Create a lead manually (from UI or internal process).
     *
     * @param array $data Validated lead data
     */
    public function createLead(array $data): Lead
    {
        return DB::transaction(function () use ($data) {
            $campaign = $this->campaignRepository->findById($data['campaign_id']);

            if (! $campaign) {
                throw new \InvalidArgumentException('Campaign not found');
            }

            // Ensure client_id is set from campaign
            $data['client_id'] = $campaign->client_id;

            // Check for duplicate
            $existingLead = $this->leadRepository->findByPhoneAndCampaign(
                $data['phone'],
                $data['campaign_id']
            );

            if ($existingLead) {
                Log::info('Existing lead found, updating instead of creating duplicate', [
                    'lead_id' => $existingLead->id,
                    'phone' => $data['phone'],
                ]);

                $lead = $this->leadRepository->update($existingLead, $data);

                LeadStageChanged::dispatchIf(
                    $this->stageChanged($existingLead, $lead),
                    $lead,
                    $this->computeStage($existingLead),
                    $this->computeStage($lead)
                );

                return $lead;
            }

            // Set defaults for new lead
            $data['status'] = $data['status'] ?? LeadStatus::PENDING;
            $data['webhook_sent'] = $data['webhook_sent'] ?? false;

            $lead = $this->leadRepository->create($data);

            LeadCreated::dispatch($lead, 'manual');

            return $lead;
        });
    }

    /**
     * Find the campaign for an incoming lead.
     */
    public function findCampaignForLead(array $leadData): ?Campaign
    {
        // 1. Direct campaign_id
        if (isset($leadData['campaign_id'])) {
            $campaign = $this->campaignRepository->findById($leadData['campaign_id']);
            if ($campaign) {
                return $campaign;
            }
        }

        // 2. By slug (accepts: 'slug', 'campaign_slug', 'campaign')
        $slug = $leadData['slug'] ?? $leadData['campaign_slug'] ?? $leadData['campaign'] ?? null;
        if ($slug) {
            $campaign = $this->campaignRepository->findBySlug($slug);
            if ($campaign) {
                return $campaign;
            }
        }

        // 3. First active campaign (fallback)
        $activeCampaigns = $this->campaignRepository->getActive();

        return $activeCampaigns->first();
    }

    /**
     * Normalize phone number using campaign context.
     */
    private function normalizePhone(string $phone, ?Campaign $campaign): string
    {
        return PhoneHelper::normalizeForLead($phone, $campaign);
    }

    /**
     * Build lead data array from raw input.
     */
    private function buildLeadData(array $leadData, Campaign $campaign, string $phone): array
    {
        return [
            'phone' => $phone,
            'name' => $leadData['name'] ?? null,
            'city' => $leadData['city'] ?? null,
            'option_selected' => $leadData['option_selected'] ?? null,
            'campaign_id' => $campaign->id,
            'client_id' => $campaign->client_id,
            'status' => $leadData['status'] ?? LeadStatus::PENDING,
            'source' => $leadData['source'] ?? LeadSource::WEBHOOK_INICIAL,
            'sent_at' => now(),
            'intention' => $leadData['intention'] ?? null,
            'notes' => $leadData['notes'] ?? null,
        ];
    }

    /**
     * Update an existing lead with new data.
     */
    private function updateExistingLead(Lead $lead, array $data): Lead
    {
        Log::info('Updating existing lead from webhook', [
            'lead_id' => $lead->id,
            'phone' => $data['phone'],
        ]);

        return $this->leadRepository->update($lead, $data);
    }

    /**
     * Create a new lead.
     */
    private function createNewLead(array $data, array $leadData): Lead
    {
        $data['webhook_sent'] = false;
        $data['webhook_result'] = null;
        $data['created_by'] = $leadData['created_by'] ?? null;

        Log::info('Creating new lead from webhook', [
            'phone' => $data['phone'],
            'campaign_id' => $data['campaign_id'],
        ]);

        return $this->leadRepository->create($data);
    }

    /**
     * Dispatch domain events for the lead.
     */
    private function dispatchEvents(Lead $lead, bool $isNew): void
    {
        if ($isNew) {
            LeadCreated::dispatch($lead, 'webhook');
        } else {
            // For updates, we emit stage change if applicable
            // The previous state would need to be tracked - simplified here
            LeadStageChanged::dispatch(
                $lead,
                LeadStage::INBOX, // Simplified: webhook updates usually stay in same stage
                $this->computeStage($lead)
            );
        }
    }

    /**
     * Get the current stage for a lead (now persisted directly).
     */
    private function computeStage(Lead $lead): LeadStage
    {
        return $lead->stage ?? LeadStage::INBOX;
    }

    /**
     * Check if stage changed between old and new lead state.
     */
    private function stageChanged(Lead $oldLead, Lead $newLead): bool
    {
        return $oldLead->stage !== $newLead->stage;
    }
}

