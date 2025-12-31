<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Enums\LeadIntention;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Helpers\PhoneHelper;
use App\Jobs\Lead\AnalyzeLeadIntentionJob;
use App\Models\Integration\Source;
use App\Models\Lead\Lead;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Services\Webhook\WebhookDispatcherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for WhatsApp-specific lead operations.
 *
 * Single Responsibility: Handle lead lookup, creation, and intention
 * updates from WhatsApp messages.
 */
class LeadWhatsAppService
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly WebhookDispatcherService $webhookDispatcher
    ) {}

    /**
     * Find or create a lead from an incoming WhatsApp message.
     *
     * Priority:
     * 1. Lead waiting for WhatsApp response (intention_status=pending)
     * 2. Most recent lead with this phone
     * 3. Create new lead if none found
     *
     * @param string $phone Phone number from WhatsApp
     * @param array|null $whatsappData Additional data (pushName, etc)
     * @param Source|null $source The WhatsApp source that received the message
     * @param string|null $defaultCampaignId Fallback campaign ID
     */
    public function findOrCreateFromWhatsApp(
        string $phone,
        ?array $whatsappData = null,
        ?Source $source = null,
        ?string $defaultCampaignId = null
    ): Lead {
        // Normalize phone using source context if available
        $country = $this->resolveCountryFromSource($source);
        $normalizedPhone = PhoneHelper::normalizeWithCountry($phone, $country);

        // Priority 1: Lead awaiting WhatsApp response
        $leadAwaitingResponse = $this->leadRepository->findByPhone($normalizedPhone);

        // Use repository method with additional conditions
        $leadAwaitingResponse = Lead::where('phone', $normalizedPhone)
            ->where('intention_status', LeadIntentionStatus::PENDING)
            ->where('intention_origin', LeadIntentionOrigin::WHATSAPP)
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($leadAwaitingResponse) {
            Log::info('Lead found awaiting WhatsApp response', [
                'lead_id' => $leadAwaitingResponse->id,
                'phone' => $normalizedPhone,
            ]);

            $this->updateContactInfoIfBetter($leadAwaitingResponse, $whatsappData);

            return $leadAwaitingResponse;
        }

        // Priority 2: Most recent lead with this phone
        $existingLead = Lead::where('phone', $normalizedPhone)
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($existingLead) {
            Log::info('Existing lead found by phone', [
                'lead_id' => $existingLead->id,
                'phone' => $normalizedPhone,
            ]);

            $this->updateContactInfoIfBetter($existingLead, $whatsappData);

            return $existingLead;
        }

        // Priority 3: Create new lead
        return $this->createLeadFromWhatsApp($normalizedPhone, $whatsappData, $defaultCampaignId);
    }

    /**
     * Update lead intention based on incoming message content.
     *
     * Uses keyword detection first (if enabled), then schedules AI analysis.
     */
    public function updateIntentionFromMessage(Lead $lead, string $messageContent): void
    {
        // Skip if already finalized
        if ($lead->intention_status === LeadIntentionStatus::FINALIZED) {
            Log::info('Lead intention already finalized, skipping analysis', [
                'lead_id' => $lead->id,
                'intention' => $lead->intention,
            ]);

            return;
        }

        $useKeywordsFirst = config('services.openai.use_keywords_first', false);
        $detectedIntention = null;

        if ($useKeywordsFirst) {
            $detectedIntention = $this->analyzeIntentionWithKeywords($messageContent);
        }

        $updateData = [];

        if ($detectedIntention) {
            $updateData = $this->buildFinalizedIntentionData($detectedIntention, $lead);

            Log::info('Intention detected via keywords', [
                'lead_id' => $lead->id,
                'intention' => $detectedIntention,
            ]);
        } else {
            $updateData = $this->buildPendingIntentionData($messageContent, $lead);
            $this->scheduleAIAnalysis($lead);

            Log::info('Scheduling AI analysis for intention', [
                'lead_id' => $lead->id,
            ]);
        }

        // Ensure WhatsApp source
        if ($lead->source !== LeadSource::WHATSAPP) {
            $updateData['source'] = LeadSource::WHATSAPP;
        }

        if (! empty($updateData)) {
            $lead = $this->leadRepository->update($lead, $updateData);

            // Dispatch webhook if intention was finalized
            if ($detectedIntention && in_array($detectedIntention, ['interested', 'not_interested'])) {
                $this->dispatchIntentionWebhook($lead, $detectedIntention);
            }
        }
    }

    /**
     * Update contact info from WhatsApp if it's better than current.
     */
    public function updateContactInfoIfBetter(Lead $lead, ?array $whatsappData): void
    {
        if (! $whatsappData) {
            return;
        }

        $pushName = $whatsappData['pushName'] ?? $whatsappData['name'] ?? null;

        if (! $pushName) {
            return;
        }

        if ($this->shouldUpdateName($lead, $pushName)) {
            $this->leadRepository->update($lead, ['name' => $pushName]);

            Log::info('Lead name updated from WhatsApp', [
                'lead_id' => $lead->id,
                'old_name' => $lead->name,
                'new_name' => $pushName,
            ]);
        }
    }

    /**
     * Resolve country code from source configuration.
     * Falls back to campaign country, then default.
     */
    private function resolveCountryFromSource(?Source $source): string
    {
        if ($source && ! empty($source->config['country'])) {
            return $source->config['country'];
        }

        // Could also check source's associated campaign
        // For now, use a sensible default
        return config('app.default_country_code', 'AR');
    }

    /**
     * Create a new lead from WhatsApp message.
     */
    private function createLeadFromWhatsApp(
        string $normalizedPhone,
        ?array $whatsappData,
        ?string $defaultCampaignId
    ): Lead {
        $campaignId = $defaultCampaignId;

        if (! $campaignId) {
            $activeCampaigns = $this->campaignRepository->getActive();

            if ($activeCampaigns->isEmpty()) {
                throw new \Exception('No active campaigns available to associate lead');
            }

            $campaignId = $activeCampaigns->first()->id;
        }

        Log::info('Creating new lead from WhatsApp', [
            'phone' => $normalizedPhone,
            'campaign_id' => $campaignId,
        ]);

        $name = $whatsappData['pushName'] ?? $whatsappData['name'] ?? 'Lead desde WhatsApp';

        return $this->leadRepository->create([
            'phone' => $normalizedPhone,
            'name' => $name,
            'campaign_id' => $campaignId,
            'status' => LeadStatus::PENDING,
            'source' => LeadSource::WHATSAPP,
            'sent_at' => now(),
        ]);
    }

    /**
     * Analyze intention using keyword matching.
     */
    private function analyzeIntentionWithKeywords(string $content): ?string
    {
        $contentLower = mb_strtolower($content);

        $interestedKeywords = [
            'sí', 'si', 'yes', 'interesado', 'interesada', 'quiero',
            'me interesa', 'info', 'información', 'mas info', 'más info',
            'dame', 'llamame', 'llámame', 'contactame', 'contáctame',
            'ok', 'dale', 'perfecto', 'claro', 'bueno', 'bien', 'hola',
            'buenos dias', 'buenas tardes', 'buenas noches', 'buen dia',
            'gracias', 'de acuerdo',
        ];

        foreach ($interestedKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return LeadIntention::INTERESTED->value;
            }
        }

        $notInterestedKeywords = [
            'no', 'nope', 'no gracias', 'no me interesa', 'no quiero',
            'no estoy interesado', 'no estoy interesada', 'baja', 'borrar',
            'eliminar', 'remover', 'stop', 'cancelar', 'no molesten',
            'dejame en paz', 'no molestar',
        ];

        foreach ($notInterestedKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return LeadIntention::NOT_INTERESTED->value;
            }
        }

        return null;
    }

    /**
     * Build update data for finalized intention.
     */
    private function buildFinalizedIntentionData(string $intention, Lead $lead): array
    {
        $data = [
            'intention' => $intention,
            'intention_status' => LeadIntentionStatus::FINALIZED,
            'intention_decided_at' => now(),
            'intention_origin' => LeadIntentionOrigin::WHATSAPP,
        ];

        if ($intention === LeadIntention::INTERESTED->value) {
            if ($lead->status === LeadStatus::PENDING) {
                $data['status'] = LeadStatus::IN_PROGRESS;
            }
        } elseif ($intention === LeadIntention::NOT_INTERESTED->value) {
            $data['status'] = LeadStatus::CLOSED;
        }

        return $data;
    }

    /**
     * Build update data for pending intention (awaiting AI analysis).
     */
    private function buildPendingIntentionData(string $messageContent, Lead $lead): array
    {
        $data = ['intention' => $messageContent];

        if ($lead->status === LeadStatus::PENDING) {
            $data['status'] = LeadStatus::IN_PROGRESS;
        }

        return $data;
    }

    /**
     * Schedule AI analysis with debouncing.
     */
    private function scheduleAIAnalysis(Lead $lead): void
    {
        $cacheKey = "lead_intention_analysis:{$lead->id}";
        $currentVersion = Cache::get($cacheKey, 0);
        $newVersion = $currentVersion + 1;

        Cache::put($cacheKey, $newVersion, now()->addMinutes(10));

        $delay = now()->addSeconds(config('services.openai.analysis_delay_seconds', 8));

        AnalyzeLeadIntentionJob::dispatch($lead->id, $newVersion)
            ->delay($delay)
            ->onQueue('default');

        Log::info('AI analysis job scheduled', [
            'lead_id' => $lead->id,
            'version' => $newVersion,
            'delay_seconds' => config('services.openai.analysis_delay_seconds', 8),
        ]);
    }

    /**
     * Dispatch intention webhook.
     */
    private function dispatchIntentionWebhook(Lead $lead, string $intention): void
    {
        try {
            $this->webhookDispatcher->dispatchLeadIntentionWebhook($lead);
        } catch (\Exception $e) {
            Log::error('Error dispatching intention webhook', [
                'lead_id' => $lead->id,
                'intention' => $intention,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine if we should update the lead's name.
     */
    private function shouldUpdateName(Lead $lead, string $newName): bool
    {
        if (empty($lead->name)) {
            return true;
        }

        $genericNames = [
            'lead sin nombre', 'sin nombre', 'unknown', 'n/a',
            'lead', 'lead desde whatsapp',
        ];

        $currentName = trim(strtolower($lead->name));

        foreach ($genericNames as $generic) {
            if (str_contains($currentName, $generic)) {
                return true;
            }
        }

        if ($lead->name === $lead->phone) {
            return true;
        }

        return false;
    }
}

