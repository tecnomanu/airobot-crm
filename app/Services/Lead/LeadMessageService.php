<?php

namespace App\Services\Lead;

use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Models\Lead\LeadMessage;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Lead Messages (WhatsApp/SMS)
 * Replaces the old LeadInteractionService for messaging
 */
class LeadMessageService
{
    /**
     * Get messages for a lead
     */
    public function getLeadMessages(string $leadId, int $limit = 10)
    {
        return LeadMessage::where('lead_id', $leadId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Create message from WhatsApp (inbound/outbound)
     */
    public function createFromWhatsAppMessage(
        string $leadId,
        ?string $campaignId,
        string $content,
        array $metadata,
        ?string $externalProviderId,
        string $phone,
        MessageDirection $direction = MessageDirection::INBOUND,
        MessageStatus $status = MessageStatus::DELIVERED
    ): LeadMessage {
        // Avoid duplicates by external_provider_id
        if ($externalProviderId) {
            $existing = LeadMessage::where('external_provider_id', $externalProviderId)->first();
            if ($existing) {
                Log::info('Mensaje duplicado detectado', [
                    'external_provider_id' => $externalProviderId,
                    'message_id' => $existing->id,
                ]);

                return $existing;
            }
        }

        return LeadMessage::create([
            'lead_id' => $leadId,
            'campaign_id' => $campaignId,
            'channel' => MessageChannel::WHATSAPP,
            'direction' => $direction,
            'status' => $status,
            'content' => $content,
            'metadata' => $metadata,
            'external_provider_id' => $externalProviderId,
            'phone' => $phone,
        ]);
    }

    /**
     * Create generic message
     */
    public function create(array $data): LeadMessage
    {
        return LeadMessage::create([
            'lead_id' => $data['lead_id'],
            'campaign_id' => $data['campaign_id'] ?? null,
            'channel' => $data['channel'] ?? MessageChannel::WHATSAPP,
            'direction' => $data['direction'] ?? MessageDirection::INBOUND,
            'status' => $data['status'] ?? MessageStatus::DELIVERED,
            'content' => $data['content'],
            'metadata' => $data['metadata'] ?? null,
            'external_provider_id' => $data['external_provider_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'attachments' => $data['attachments'] ?? null,
        ]);
    }
}

