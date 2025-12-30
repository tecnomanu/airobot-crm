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

class LeadController extends Controller
{
    public function __construct(
        private LeadService $leadService,
        private CampaignService $campaignService,
        private ClientService $clientService
    ) {}

    /**
     * Unified Leads view with tabs: Inbox, Active Pipeline, Sales Ready
     */
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', 'inbox');

        if (!in_array($tab, ['inbox', 'active', 'sales_ready'])) {
            $tab = 'inbox';
        }

        $filters = [
            'campaign_id' => $request->input('campaign_id'),
            'client_id' => $request->input('client_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $leads = $this->leadService->getLeadsForManager(
            $tab,
            $filters,
            $request->input('per_page', 15)
        );

        $tabCounts = $this->leadService->getTabCounts($filters);

        $campaigns = $this->campaignService->getActiveCampaigns();
        $clients = $this->clientService->getActiveClients();

        return Inertia::render('Leads/Index', [
            'leads' => LeadResource::collection($leads),
            'campaigns' => $campaigns,
            'clients' => $clients,
            'filters' => $filters,
            'activeTab' => $tab,
            'tabCounts' => $tabCounts,
        ]);
    }

    /**
     * Show lead detail
     */
    public function show(string $id): Response
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        $lead->load([
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'campaign.client',
            'calls' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            }
        ]);

        // Convert resource to array directly to avoid serialization issues
        $leadData = (new LeadResource($lead))->toArray(request());

        return Inertia::render('Leads/Show', [
            'lead' => $leadData,
        ]);
    }

    /**
     * Store new lead
     */
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            unset($data['tab_placement']);

            $this->leadService->createLead(
                array_merge($data, [
                    'created_by' => Auth::id(),
                ])
            );

            return redirect()->back()
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
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Retry automation for a specific lead
     */
    public function retryAutomation(string $id): RedirectResponse
    {
        try {
            $this->leadService->retryAutomation($id);

            return redirect()->back()
                ->with('success', 'Automation retried successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Retry automation for all failed leads
     */
    public function retryAutomationBatch(Request $request): RedirectResponse
    {
        try {
            $filters = $request->input('filters', []);
            $results = $this->leadService->retryAutomationBatch($filters);

            return redirect()->back()
                ->with('success', "Batch retry completed. Success: {$results['success']}, Failed: {$results['failed']}");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Quick action: Call lead
     */
    public function initiateCall(string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404);
        }

        return redirect()->back()
            ->with('info', 'Función de llamada en desarrollo');
    }

    /**
     * Quick action: Send WhatsApp message
     */
    public function initiateWhatsapp(string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404);
        }

        return redirect()->back()
            ->with('info', 'Función de WhatsApp en desarrollo');
    }
    /**
     * Delete lead
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $this->leadService->deleteLead($id);

            return redirect()->route('leads.index')
                ->with('success', 'Lead eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Import leads from CSV
     */
    public function importCSV(Request $request): RedirectResponse
    {
        $request->validate([
            'leads' => 'required|array',
            'leads.*.phone' => 'required|string',
            'leads.*.campaign_id' => 'required|exists:campaigns,id',
        ]);

        try {
            $leads = $request->input('leads');
            $successCount = 0;
            $errors = [];

            foreach ($leads as $index => $leadData) {
                try {
                    // Add creator
                    $leadData['created_by'] = Auth::id();

                    $this->leadService->createLead($leadData);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Fila " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            if (count($errors) > 0) {
                // Return success with warning if some failed
                // Or if all failed, return error
                if ($successCount > 0) {
                    return redirect()->back()
                        ->with('success', "$successCount leads importados exitosamente")
                        ->with('warning', count($errors) . " leads fallaron. Ver logs para detalles.");
                } else {
                    return redirect()->back()
                        ->with('error', "Falló la importación: " . implode(', ', array_slice($errors, 0, 3)));
                }
            }

            return redirect()->back()
                ->with('success', "$successCount leads importados exitosamente");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error general al importar: ' . $e->getMessage());
        }
    }
}
