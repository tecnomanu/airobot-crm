<?php

namespace App\DTOs\Reporting;

/**
 * DTO para métricas globales del Dashboard
 */
class GlobalMetrics
{
    public function __construct(
        public int $totalLeads,
        public int $activeCampaigns,
        public int $totalCalls,
        public float $totalCost,
        public int $convertedLeads,
        public float $conversionRate,
        public int $pendingLeads,
        public int $activeClients,
    ) {}

    public function toArray(): array
    {
        return [
            'total_leads' => $this->totalLeads,
            'active_campaigns' => $this->activeCampaigns,
            'total_calls' => $this->totalCalls,
            'total_cost' => round($this->totalCost, 2),
            'converted_leads' => $this->convertedLeads,
            'conversion_rate' => round($this->conversionRate, 2),
            'pending_leads' => $this->pendingLeads,
            'active_clients' => $this->activeClients,
        ];
    }
}

