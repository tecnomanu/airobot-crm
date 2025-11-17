<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReportingService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ReportingService $reportingService
    ) {}

    public function index(): Response
    {
        // Métricas globales optimizadas
        $metrics = $this->reportingService->getGlobalDashboardMetrics();

        // Últimos leads (optimizado, solo 10)
        $recentLeads = $this->reportingService->getRecentLeads(10);

        // Rendimiento de campañas (top 5)
        $campaignPerformance = $this->reportingService->getCampaignPerformance(null, 5);

        // Leads por estado
        $leadsByStatus = $this->reportingService->getLeadsByStatus();

        // Clientes activos
        $activeClients = $this->reportingService->getActiveClients();

        return Inertia::render('Dashboard', [
            'summary' => $metrics->toArray(),
            'recentLeads' => $recentLeads,
            'campaignPerformance' => $campaignPerformance,
            'leadsByStatus' => $leadsByStatus,
            'activeClients' => $activeClients,
        ]);
    }
}
