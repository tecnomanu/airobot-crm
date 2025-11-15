<?php

namespace App\Repositories\Eloquent;

use App\Models\LeadInteraction;
use App\Repositories\Interfaces\LeadInteractionRepositoryInterface;
use Illuminate\Support\Collection;

class LeadInteractionRepository implements LeadInteractionRepositoryInterface
{
    public function create(array $data): LeadInteraction
    {
        return LeadInteraction::create($data);
    }

    public function getByLead(int $leadId, int $limit = 10): Collection
    {
        return LeadInteraction::where('lead_id', $leadId)
            ->with(['campaign'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function findByExternalId(string $externalId): ?LeadInteraction
    {
        return LeadInteraction::where('external_id', $externalId)->first();
    }

    public function getRecentByPhone(string $phone, int $limit = 5): Collection
    {
        return LeadInteraction::where('phone', $phone)
            ->with(['lead', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getByCampaign(int $campaignId): Collection
    {
        return LeadInteraction::where('campaign_id', $campaignId)
            ->with(['lead'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

