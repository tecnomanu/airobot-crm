<?php

namespace App\Services;

use App\Enums\DispatchStatus;
use App\Enums\DispatchTrigger;
use App\Enums\DispatchType;
use App\Enums\LeadCloseReason;
use App\Models\Campaign\CampaignIntentionAction;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadDispatchAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeadDispatchService
{
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_MINUTES = 5;

    public function __construct(
        private ?GoogleSheetsService $googleSheetsService = null,
    ) {}

    /**
     * Dispatch lead data based on close reason.
     */
    public function dispatchForCloseReason(Lead $lead, LeadCloseReason $closeReason): void
    {
        $trigger = DispatchTrigger::fromCloseReason($closeReason);

        if (!$trigger) {
            Log::debug('No dispatch trigger for close reason', [
                'lead_id' => $lead->id,
                'close_reason' => $closeReason->value,
            ]);
            return;
        }

        $intentionType = $closeReason->toIntentionType();
        if (!$intentionType) {
            return;
        }

        $this->dispatchForIntention($lead, $intentionType, $trigger);
    }

    /**
     * Dispatch lead data for a specific intention type.
     */
    public function dispatchForIntention(Lead $lead, string $intentionType, DispatchTrigger $trigger): void
    {
        if (!$lead->campaign_id) {
            Log::debug('No campaign for lead dispatch', ['lead_id' => $lead->id]);
            return;
        }

        // Find configured action for this intention type
        $action = CampaignIntentionAction::where('campaign_id', $lead->campaign_id)
            ->where('intention_type', $intentionType)
            ->where('enabled', true)
            ->first();

        if (!$action) {
            Log::debug('No intention action configured', [
                'lead_id' => $lead->id,
                'campaign_id' => $lead->campaign_id,
                'intention_type' => $intentionType,
            ]);
            return;
        }

        // Dispatch based on action type
        match ($action->action_type) {
            'webhook' => $this->dispatchWebhook($lead, $action, $trigger),
            'spreadsheet' => $this->dispatchGoogleSheet($lead, $action, $trigger),
            default => null,
        };
    }

    /**
     * Dispatch lead to webhook.
     */
    public function dispatchWebhook(
        Lead $lead,
        CampaignIntentionAction $action,
        DispatchTrigger $trigger
    ): ?LeadDispatchAttempt {
        if (!$action->webhook_id || !$action->webhook) {
            return null;
        }

        // Check idempotency - don't send if already successful
        if ($this->hasSuccessfulDispatch($lead, $trigger, $action->webhook_id)) {
            Log::debug('Skipping duplicate webhook dispatch', [
                'lead_id' => $lead->id,
                'trigger' => $trigger->value,
                'webhook_id' => $action->webhook_id,
            ]);
            return null;
        }

        $webhook = $action->webhook;
        $url = $webhook->config['url'] ?? null;

        if (!$url) {
            Log::warning('Webhook URL not configured', ['source_id' => $webhook->id]);
            return null;
        }

        $payload = $this->buildWebhookPayload($lead);

        // Create dispatch attempt
        $attempt = LeadDispatchAttempt::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->resolved_client_id,
            'campaign_id' => $lead->campaign_id,
            'type' => DispatchType::WEBHOOK,
            'trigger' => $trigger,
            'destination_id' => $action->webhook_id,
            'request_payload' => $payload,
            'request_url' => $url,
            'request_method' => $webhook->config['method'] ?? 'POST',
            'status' => DispatchStatus::PENDING,
        ]);

        // Execute webhook
        $this->executeWebhook($attempt);

        return $attempt;
    }

    /**
     * Dispatch lead to Google Sheet.
     */
    public function dispatchGoogleSheet(
        Lead $lead,
        CampaignIntentionAction $action,
        DispatchTrigger $trigger
    ): ?LeadDispatchAttempt {
        if (!$action->google_spreadsheet_id) {
            return null;
        }

        // Check idempotency
        $destinationId = $action->id;
        if ($this->hasSuccessfulDispatch($lead, $trigger, $destinationId)) {
            Log::debug('Skipping duplicate sheet dispatch', [
                'lead_id' => $lead->id,
                'trigger' => $trigger->value,
            ]);
            return null;
        }

        $payload = $this->buildSheetPayload($lead);

        // Create dispatch attempt
        $attempt = LeadDispatchAttempt::create([
            'lead_id' => $lead->id,
            'client_id' => $lead->resolved_client_id,
            'campaign_id' => $lead->campaign_id,
            'type' => DispatchType::GOOGLE_SHEET,
            'trigger' => $trigger,
            'destination_id' => $destinationId,
            'request_payload' => $payload,
            'request_url' => "sheets/{$action->google_spreadsheet_id}/{$action->google_sheet_name}",
            'status' => DispatchStatus::PENDING,
        ]);

        // Execute sheet export
        $this->executeSheetExport($attempt, $action);

        return $attempt;
    }

    /**
     * Retry a failed dispatch attempt.
     */
    public function retryDispatch(LeadDispatchAttempt $attempt): LeadDispatchAttempt
    {
        if (!$attempt->can_retry) {
            throw new \InvalidArgumentException('This attempt cannot be retried');
        }

        if ($attempt->attempt_no >= self::MAX_RETRY_ATTEMPTS) {
            $attempt->update([
                'status' => DispatchStatus::FAILED,
                'error_message' => 'Max retry attempts exceeded',
            ]);
            return $attempt;
        }

        $attempt->incrementAttempt();

        // Re-execute based on type
        match ($attempt->type) {
            DispatchType::WEBHOOK => $this->executeWebhook($attempt),
            DispatchType::GOOGLE_SHEET => $this->executeSheetExportFromAttempt($attempt),
        };

        return $attempt->fresh();
    }

    /**
     * Get dispatch attempts for a lead.
     */
    public function getAttemptsForLead(Lead $lead): \Illuminate\Database\Eloquent\Collection
    {
        return $lead->dispatchAttempts()->latest()->get();
    }

    // ==========================================
    // PRIVATE METHODS
    // ==========================================

    private function hasSuccessfulDispatch(Lead $lead, DispatchTrigger $trigger, ?string $destinationId): bool
    {
        return LeadDispatchAttempt::existsSuccessful($lead->id, $trigger, $destinationId)->exists();
    }

    private function buildWebhookPayload(Lead $lead): array
    {
        return [
            'lead_id' => $lead->id,
            'phone' => $lead->phone,
            'name' => $lead->name,
            'email' => $lead->email,
            'city' => $lead->city,
            'country' => $lead->country,
            'campaign_id' => $lead->campaign_id,
            'campaign_name' => $lead->campaign?->name,
            'stage' => $lead->stage?->value,
            'close_reason' => $lead->close_reason?->value,
            'close_notes' => $lead->close_notes,
            'intention' => $lead->intention,
            'option_selected' => $lead->option_selected,
            'assigned_to' => $lead->assignee?->name,
            'notes' => $lead->notes,
            'tags' => $lead->tags,
            'created_at' => $lead->created_at?->toIso8601String(),
            'closed_at' => $lead->closed_at?->toIso8601String(),
        ];
    }

    private function buildSheetPayload(Lead $lead): array
    {
        return [
            $lead->created_at?->format('Y-m-d H:i:s'),
            $lead->name,
            $lead->phone,
            $lead->email,
            $lead->city,
            $lead->campaign?->name,
            $lead->stage?->label(),
            $lead->close_reason?->label(),
            $lead->intention,
            $lead->notes,
            $lead->assignee?->name,
        ];
    }

    private function executeWebhook(LeadDispatchAttempt $attempt): void
    {
        try {
            $method = strtolower($attempt->request_method ?? 'post');
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Dispatch-Attempt' => (string) $attempt->attempt_no,
                ])
                ->$method($attempt->request_url, $attempt->request_payload);

            if ($response->successful()) {
                $attempt->markSuccess($response->status(), $response->body());

                Log::info('Webhook dispatch successful', [
                    'attempt_id' => $attempt->id,
                    'lead_id' => $attempt->lead_id,
                    'status' => $response->status(),
                ]);
            } else {
                $attempt->markFailed(
                    $response->status(),
                    $response->body(),
                    "HTTP {$response->status()}",
                    $attempt->attempt_no < self::MAX_RETRY_ATTEMPTS,
                    self::RETRY_DELAY_MINUTES
                );

                Log::warning('Webhook dispatch failed', [
                    'attempt_id' => $attempt->id,
                    'lead_id' => $attempt->lead_id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            $attempt->markFailed(
                null,
                null,
                $e->getMessage(),
                $attempt->attempt_no < self::MAX_RETRY_ATTEMPTS,
                self::RETRY_DELAY_MINUTES
            );

            Log::error('Webhook dispatch exception', [
                'attempt_id' => $attempt->id,
                'lead_id' => $attempt->lead_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function executeSheetExport(LeadDispatchAttempt $attempt, CampaignIntentionAction $action): void
    {
        if (!$this->googleSheetsService) {
            $attempt->markFailed(null, null, 'Google Sheets service not configured', false);
            return;
        }

        try {
            $this->googleSheetsService->appendRow(
                $action->google_integration_id,
                $action->google_spreadsheet_id,
                $action->google_sheet_name ?? 'Sheet1',
                $attempt->request_payload
            );

            $attempt->markSuccess(200, 'Row appended successfully');

            Log::info('Sheet dispatch successful', [
                'attempt_id' => $attempt->id,
                'lead_id' => $attempt->lead_id,
            ]);
        } catch (\Exception $e) {
            $attempt->markFailed(
                null,
                null,
                $e->getMessage(),
                $attempt->attempt_no < self::MAX_RETRY_ATTEMPTS,
                self::RETRY_DELAY_MINUTES
            );

            Log::error('Sheet dispatch exception', [
                'attempt_id' => $attempt->id,
                'lead_id' => $attempt->lead_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function executeSheetExportFromAttempt(LeadDispatchAttempt $attempt): void
    {
        if (!$attempt->destination_id) {
            $attempt->markFailed(null, null, 'No destination configured', false);
            return;
        }

        $action = CampaignIntentionAction::find($attempt->destination_id);
        if (!$action) {
            $attempt->markFailed(null, null, 'Intention action not found', false);
            return;
        }

        $this->executeSheetExport($attempt, $action);
    }
}

