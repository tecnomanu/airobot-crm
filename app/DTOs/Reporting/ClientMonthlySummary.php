<?php

namespace App\DTOs\Reporting;

/**
 * DTO para resumen mensual de cliente
 */
class ClientMonthlySummary
{
    /**
     * @param  CampaignSummary[]  $campaigns
     */
    public function __construct(
        public int $clientId,
        public string $clientName,
        public string $period,
        public int $totalLeads,
        public int $pendingLeads,
        public int $contactedLeads,
        public int $convertedLeads,
        public int $totalCalls,
        public int $completedCalls,
        public int $totalDurationSeconds,
        public float $totalCost,
        public array $campaigns,
    ) {}

    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
            'period' => $this->period,
            'totals' => [
                'total_leads' => $this->totalLeads,
                'pending_leads' => $this->pendingLeads,
                'contacted_leads' => $this->contactedLeads,
                'converted_leads' => $this->convertedLeads,
                'conversion_rate' => $this->totalLeads > 0 ?
                    round(($this->convertedLeads / $this->totalLeads) * 100, 2) : 0,
                'total_calls' => $this->totalCalls,
                'completed_calls' => $this->completedCalls,
                'total_duration_seconds' => $this->totalDurationSeconds,
                'total_duration_minutes' => round($this->totalDurationSeconds / 60, 2),
                'total_duration_hours' => round($this->totalDurationSeconds / 3600, 2),
                'total_cost' => round($this->totalCost, 2),
                'avg_cost_per_lead' => $this->totalLeads > 0 ?
                    round($this->totalCost / $this->totalLeads, 2) : 0,
                'avg_cost_per_call' => $this->totalCalls > 0 ?
                    round($this->totalCost / $this->totalCalls, 2) : 0,
            ],
            'campaigns' => array_map(fn ($campaign) => $campaign->toArray(), $this->campaigns),
        ];
    }
}
