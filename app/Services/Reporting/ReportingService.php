<?php

namespace App\Services\Reporting;

use App\DTOs\Reporting\CampaignSummary;
use App\DTOs\Reporting\ClientMonthlySummary;
use App\DTOs\Reporting\GlobalMetrics;
use App\Enums\CampaignStatus;
use App\Enums\ClientStatus;
use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Repositories\Interfaces\LeadCallRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function __construct(
        private LeadRepositoryInterface $leadRepository,
        private CampaignRepositoryInterface $campaignRepository,
        private ClientRepositoryInterface $clientRepository,
        private LeadCallRepositoryInterface $leadCallRepository
    ) {}

    /**
     * Get global metrics for Dashboard
     */
    public function getGlobalDashboardMetrics(): GlobalMetrics
    {
        $leadsStats = DB::table('leads')
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as converted'),
            ])
            ->first();

        $callsStats = DB::table('lead_calls')
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(cost) as total_cost'),
            ])
            ->first();

        $activeCampaigns = DB::table('campaigns')
            ->where('status', CampaignStatus::ACTIVE->value)
            ->count();

        $activeClients = DB::table('clients')
            ->where('status', ClientStatus::ACTIVE->value)
            ->count();

        $totalLeads = $leadsStats->total ?? 0;
        $convertedLeads = $leadsStats->converted ?? 0;
        $conversionRate = $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;

        return new GlobalMetrics(
            totalLeads: $totalLeads,
            activeCampaigns: $activeCampaigns,
            totalCalls: $callsStats->total ?? 0,
            totalCost: $callsStats->total_cost ?? 0.0,
            convertedLeads: $convertedLeads,
            conversionRate: $conversionRate,
            pendingLeads: $leadsStats->pending ?? 0,
            activeClients: $activeClients,
        );
    }

    /**
     * Get client monthly summary
     */
    public function getClientMonthlySummary(Client $client, Carbon $from, Carbon $to): ClientMonthlySummary
    {
        $campaignIds = $client->campaigns()->pluck('id')->toArray();

        if (empty($campaignIds)) {
            return new ClientMonthlySummary(
                clientId: $client->id,
                clientName: $client->name,
                period: $from->format('Y-m').' - '.$to->format('Y-m'),
                totalLeads: 0,
                pendingLeads: 0,
                contactedLeads: 0,
                convertedLeads: 0,
                totalCalls: 0,
                completedCalls: 0,
                totalDurationSeconds: 0,
                totalCost: 0.0,
                campaigns: [],
            );
        }

        $leadsStats = DB::table('leads')
            ->select([
                'campaign_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN status = "contacted" THEN 1 ELSE 0 END) as contacted'),
                DB::raw('SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as converted'),
            ])
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        $callsStats = DB::table('lead_calls')
            ->select([
                'campaign_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(duration_seconds) as total_duration'),
                DB::raw('SUM(cost) as total_cost'),
            ])
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween('call_date', [$from, $to])
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        $campaigns = Campaign::whereIn('id', $campaignIds)
            ->orderBy('name')
            ->get();

        $campaignSummaries = [];
        $totals = [
            'leads' => 0,
            'pending' => 0,
            'contacted' => 0,
            'converted' => 0,
            'calls' => 0,
            'completed_calls' => 0,
            'duration' => 0,
            'cost' => 0.0,
        ];

        foreach ($campaigns as $campaign) {
            $leadStats = $leadsStats->get($campaign->id);
            $callStats = $callsStats->get($campaign->id);

            $totalLeads = $leadStats->total ?? 0;
            $pendingLeads = $leadStats->pending ?? 0;
            $contactedLeads = $leadStats->contacted ?? 0;
            $convertedLeads = $leadStats->converted ?? 0;
            $totalCalls = $callStats->total ?? 0;
            $completedCalls = $callStats->completed ?? 0;
            $totalDuration = $callStats->total_duration ?? 0;
            $totalCost = $callStats->total_cost ?? 0.0;

            $totals['leads'] += $totalLeads;
            $totals['pending'] += $pendingLeads;
            $totals['contacted'] += $contactedLeads;
            $totals['converted'] += $convertedLeads;
            $totals['calls'] += $totalCalls;
            $totals['completed_calls'] += $completedCalls;
            $totals['duration'] += $totalDuration;
            $totals['cost'] += $totalCost;

            $campaignSummaries[] = new CampaignSummary(
                campaignId: $campaign->id,
                campaignName: $campaign->name,
                status: $campaign->status->label(),
                totalLeads: $totalLeads,
                pendingLeads: $pendingLeads,
                contactedLeads: $contactedLeads,
                convertedLeads: $convertedLeads,
                totalCalls: $totalCalls,
                completedCalls: $completedCalls,
                totalDurationSeconds: $totalDuration,
                totalCost: $totalCost,
            );
        }

        return new ClientMonthlySummary(
            clientId: $client->id,
            clientName: $client->name,
            period: $from->format('M Y').' - '.$to->format('M Y'),
            totalLeads: $totals['leads'],
            pendingLeads: $totals['pending'],
            contactedLeads: $totals['contacted'],
            convertedLeads: $totals['converted'],
            totalCalls: $totals['calls'],
            completedCalls: $totals['completed_calls'],
            totalDurationSeconds: $totals['duration'],
            totalCost: $totals['cost'],
            campaigns: $campaignSummaries,
        );
    }

    /**
     * Get recent leads (for Dashboard)
     */
    public function getRecentLeads(int $limit = 10): Collection
    {
        return $this->leadRepository->getRecent($limit);
    }

    /**
     * Get active clients
     */
    public function getActiveClients(): Collection
    {
        return Client::where('status', ClientStatus::ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'company', 'status']);
    }

    /**
     * Get campaign performance (for Dashboard)
     */
    public function getCampaignPerformance(?int $clientId = null, int $limit = 10): Collection
    {
        $query = Campaign::query()
            ->select([
                'campaigns.id',
                'campaigns.name',
                'campaigns.status',
                'campaigns.client_id',
            ])
            ->with('client:id,name')
            ->when($clientId, function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });

        $campaigns = $query->limit($limit)->get();

        return $campaigns->map(function ($campaign) {
            $leadsStats = DB::table('leads')
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as converted'),
                ])
                ->where('campaign_id', $campaign->id)
                ->first();

            $callsStats = DB::table('lead_calls')
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(cost) as total_cost'),
                ])
                ->where('campaign_id', $campaign->id)
                ->first();

            $totalLeads = $leadsStats->total ?? 0;
            $convertedLeads = $leadsStats->converted ?? 0;

            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status->value,
                'status_label' => $campaign->status->label(),
                'status_color' => $campaign->status->color(),
                'client_name' => $campaign->client->name ?? '',
                'total_leads' => $totalLeads,
                'converted_leads' => $convertedLeads,
                'conversion_rate' => $totalLeads > 0 ?
                    round(($convertedLeads / $totalLeads) * 100, 2) : 0,
                'total_calls' => $callsStats->total ?? 0,
                'total_call_cost' => round($callsStats->total_cost ?? 0, 2),
            ];
        });
    }

    /**
     * Get client overview metrics
     */
    public function getClientOverview(Client $client): array
    {
        $campaignIds = $client->campaigns()->pluck('id')->toArray();

        $leadsStats = DB::table('leads')
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as converted'),
            ])
            ->whereIn('campaign_id', $campaignIds)
            ->first();

        $callsStats = DB::table('lead_calls')
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(duration_seconds) as total_duration'),
                DB::raw('SUM(cost) as total_cost'),
            ])
            ->where('client_id', $client->id)
            ->first();

        return [
            'total_campaigns' => $client->campaigns()->count(),
            'active_campaigns' => $client->campaigns()->where('status', CampaignStatus::ACTIVE)->count(),
            'total_leads' => $leadsStats->total ?? 0,
            'converted_leads' => $leadsStats->converted ?? 0,
            'conversion_rate' => ($leadsStats->total ?? 0) > 0 ?
                round((($leadsStats->converted ?? 0) / ($leadsStats->total ?? 1)) * 100, 2) : 0,
            'total_calls' => $callsStats->total ?? 0,
            'total_duration_minutes' => round(($callsStats->total_duration ?? 0) / 60, 2),
            'total_cost' => round($callsStats->total_cost ?? 0, 2),
        ];
    }

    /**
     * Get leads distribution by status
     */
    public function getLeadsByStatus(): array
    {
        $stats = DB::table('leads')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $statusMap = [
            'pending' => 'Pendiente',
            'in_progress' => 'En Proceso',
            'contacted' => 'Contactado',
            'closed' => 'Cerrado',
            'invalid' => 'Inválido',
        ];

        return $stats->map(function ($stat) use ($statusMap) {
            return [
                'status' => $stat->status,
                'label' => $statusMap[$stat->status] ?? $stat->status,
                'count' => $stat->count,
            ];
        })->toArray();
    }
}
