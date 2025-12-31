<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReportingService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ReportingService $reportingService
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        
        // Determine if user is global (admin always global, supervisor without client is global)
        $isAdmin = $user->role->value === 'admin';
        $isSupervisor = $user->role->value === 'supervisor';
        $isGlobalUser = $isAdmin || ($isSupervisor && $user->client_id === null);
        $effectiveClientId = $isGlobalUser ? null : $user->client_id;

        // For sellers, also pass their user ID for assignment filtering
        $assignedTo = null;
        if ($effectiveClientId && $user->is_seller && $user->role->value === 'user') {
            $assignedTo = $user->id;
        }

        // Metrics scoped to client (or global for admin users)
        $metrics = $this->reportingService->getGlobalDashboardMetrics($effectiveClientId, $assignedTo);

        // Recent leads scoped to client
        $recentLeads = $this->reportingService->getRecentLeads(10, $effectiveClientId, $assignedTo);

        // Campaign performance scoped to client
        $campaignPerformance = $this->reportingService->getCampaignPerformance($effectiveClientId, 5);

        // Leads by status scoped to client
        $leadsByStatus = $this->reportingService->getLeadsByStatus($effectiveClientId, $assignedTo);

        // Active clients - only for global users
        $activeClients = $isGlobalUser ? $this->reportingService->getActiveClients() : collect();

        return Inertia::render('Dashboard', [
            'summary' => $metrics->toArray(),
            'recentLeads' => $recentLeads,
            'campaignPerformance' => $campaignPerformance,
            'leadsByStatus' => $leadsByStatus,
            'activeClients' => $activeClients,
        ]);
    }
}
