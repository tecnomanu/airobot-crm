<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reporting\ClientMonthlySummaryResource;
use App\Http\Resources\Reporting\GlobalMetricsResource;
use App\Http\Traits\ApiResponse;
use App\Services\Client\ClientService;
use App\Services\Reporting\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API para reportes y métricas
 */
class ReportingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ReportingService $reportingService,
        private ClientService $clientService
    ) {}

    /**
     * Obtener métricas globales del Dashboard
     * GET /api/reporting/metrics
     */
    public function globalMetrics(): GlobalMetricsResource
    {
        $metrics = $this->reportingService->getGlobalDashboardMetrics();

        return new GlobalMetricsResource($metrics);
    }

    /**
     * Obtener resumen mensual de un cliente
     * GET /api/reporting/clients/{client}/monthly-summary
     *
     * Query params:
     * - month: YYYY-MM (default: mes actual)
     */
    public function clientMonthlySummary(Request $request, int $clientId): JsonResponse
    {
        $client = $this->clientService->getClientById($clientId);

        if (! $client) {
            return $this->notFoundResponse('Client not found');
        }

        // Parsear mes
        $month = $request->input('month', now()->format('Y-m'));

        try {
            $from = Carbon::parse($month.'-01')->startOfMonth();
            $to = Carbon::parse($month.'-01')->endOfMonth();
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid month format. Use YYYY-MM', '', 400);
        }

        $summary = $this->reportingService->getClientMonthlySummary($client, $from, $to);

        return $this->successResponse(
            new ClientMonthlySummaryResource($summary),
            'Monthly summary retrieved successfully'
        );
    }

    /**
     * Obtener overview de un cliente
     * GET /api/reporting/clients/{client}/overview
     */
    public function clientOverview(int $clientId): JsonResponse
    {
        $client = $this->clientService->getClientById($clientId);

        if (! $client) {
            return $this->notFoundResponse('Client not found');
        }

        $overview = $this->reportingService->getClientOverview($client);

        return $this->successResponse($overview, 'Client overview retrieved successfully');
    }

    /**
     * Obtener rendimiento de campañas
     * GET /api/reporting/campaigns/performance
     *
     * Query params:
     * - client_id: filtrar por cliente
     * - limit: número de resultados (default: 10)
     */
    public function campaignPerformance(Request $request): JsonResponse
    {
        $clientId = $request->input('client_id');
        $limit = $request->input('limit', 10);

        $performance = $this->reportingService->getCampaignPerformance($clientId, $limit);

        return $this->successResponse($performance, 'Campaign performance retrieved successfully');
    }
}
