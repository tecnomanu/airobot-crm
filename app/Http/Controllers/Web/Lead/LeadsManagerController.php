<?php

namespace App\Http\Controllers\Web\Lead;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Resources\Lead\LeadResource;
use App\Services\Campaign\CampaignService;
use App\Services\Client\ClientService;
use App\Services\Lead\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LeadsManagerController extends Controller
{
    public function __construct(
        private LeadService $leadService,
        private CampaignService $campaignService,
        private ClientService $clientService
    ) {}

    /**
     * Unified Leads Manager view with tabs
     * 
     * Replaces separate Leads/Index and LeadsIntencion/Index views
     */
    public function index(Request $request): Response
    {
        // Determine active tab (default: inbox)
        $tab = $request->input('tab', 'inbox');

        // Validate tab value
        if (!in_array($tab, ['inbox', 'active', 'sales_ready'])) {
            $tab = 'inbox';
        }

        // Build filters
        $filters = [
            'campaign_id' => $request->input('campaign_id'),
            'client_id' => $request->input('client_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        // Get leads for current tab
        $leads = $this->leadService->getLeadsForManager(
            $tab,
            $filters,
            $request->input('per_page', 15)
        );

        // Get counts for all tabs (for badges)
        $tabCounts = $this->leadService->getTabCounts($filters);

        // Get campaigns and clients for filters
        $campaigns = $this->campaignService->getActiveCampaigns();
        $clients = $this->clientService->getActiveClients();

        return Inertia::render('LeadsManager/Index', [
            'leads' => LeadResource::collection($leads),
            'campaigns' => $campaigns,
            'clients' => $clients,
            'filters' => $filters,
            'activeTab' => $tab,
            'tabCounts' => $tabCounts,
        ]);
    }

    /**
     * Show lead detail (works for all tabs)
     */
    public function show(string $id): Response
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        // Load all interactions for timeline view
        $lead->load([
            'interactions' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'campaign.client',
            'callHistories' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            }
        ]);

        return Inertia::render('LeadsManager/Show', [
            'lead' => new LeadResource($lead),
        ]);
    }

    /**
     * Create new lead
     */
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        try {
            $this->leadService->createLead(
                array_merge($request->validated(), [
                    'created_by' => Auth::id(),
                ])
            );

            return redirect()->route('leads-manager.index')
                ->with('success', 'Lead creado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update lead
     */
    public function update(UpdateLeadRequest $request, string $id): RedirectResponse
    {
        try {
            $this->leadService->updateLead($id, $request->validated());

            return redirect()->back()
                ->with('success', 'Lead actualizado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete lead
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $this->leadService->deleteLead($id);

            return redirect()->route('leads-manager.index')
                ->with('success', 'Lead eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Retry automation for single lead
     */
    public function retryAutomation(string $id): RedirectResponse
    {
        try {
            $this->leadService->retryAutomation($id);

            return redirect()->back()
                ->with('success', 'Procesamiento reiniciado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al reiniciar procesamiento: ' . $e->getMessage());
        }
    }

    /**
     * Retry automation for multiple leads (batch)
     */
    public function retryAutomationBatch(Request $request): RedirectResponse
    {
        try {
            $filters = [
                'campaign_id' => $request->input('campaign_id'),
                'client_id' => $request->input('client_id'),
            ];

            $results = $this->leadService->retryAutomationBatch($filters);

            $message = sprintf(
                'Procesamiento completado: %d exitosos, %d fallidos de %d totales',
                $results['success'],
                $results['failed'],
                $results['total']
            );

            return redirect()->back()
                ->with($results['failed'] > 0 ? 'warning' : 'success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error en procesamiento masivo: ' . $e->getMessage());
        }
    }

    /**
     * Quick action: Call lead (redirects to call agent or external)
     */
    public function callAction(string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404);
        }

        // TODO: Implement call action logic (trigger call agent, etc.)
        return redirect()->back()
            ->with('info', 'Función de llamada en desarrollo');
    }

    /**
     * Quick action: Send WhatsApp message
     */
    public function whatsappAction(string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404);
        }

        // TODO: Implement WhatsApp action logic
        return redirect()->back()
            ->with('info', 'Función de WhatsApp en desarrollo');
    }
}
