<?php

namespace App\Http\Controllers\Web\Lead;

use App\Enums\LeadCloseReason;
use App\Enums\LeadManagerTab;
use App\Exceptions\Business\LeadStageException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\CloseLeadRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Requests\Lead\UpdateLeadStageRequest;
use App\Http\Resources\Lead\LeadDispatchAttemptResource;
use App\Http\Resources\Lead\LeadResource;
use App\Models\Lead\Lead;
use App\Models\User;
use App\Services\Campaign\CampaignService;
use App\Services\Client\ClientService;
use App\Services\Lead\LeadService;
use App\Services\LeadDispatchService;
use App\Services\LeadStageService;
use Illuminate\Http\JsonResponse;
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
        private ClientService $clientService,
        private LeadStageService $stageService,
        private LeadDispatchService $dispatchService,
    ) {}

    /**
     * Unified Leads view with tabs: Inbox, Active Pipeline, Sales Ready, Closed, Errors
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $tabEnum = LeadManagerTab::fromStringOrDefault($request->input('tab'));
        $tab = $tabEnum->value;

        // Determine if user is global (admin always global, supervisor without client is global)
        $isAdmin = $user->role->value === 'admin';
        $isSupervisor = $user->role->value === 'supervisor';
        $isGlobalUser = $isAdmin || ($isSupervisor && $user->client_id === null);
        $effectiveClientId = $isGlobalUser ? null : $user->client_id;

        $filters = [
            'campaign_id' => $request->input('campaign_id'),
            'client_id' => $request->input('client_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        // Tenant isolation: force client_id filter for non-global users
        if ($effectiveClientId) {
            $filters['client_id'] = $effectiveClientId;
            
            // Regular users who are sellers only see their assigned leads
            if ($user->is_seller && $user->role->value === 'user') {
                $filters['assigned_to'] = $user->id;
            }
        }

        $leads = $this->leadService->getLeadsForManager(
            $tab,
            $filters,
            $request->input('per_page', 15)
        );

        $tabCounts = $this->leadService->getTabCounts($filters);

        // Filter campaigns and clients based on user's tenant
        $campaigns = $effectiveClientId
            ? $this->campaignService->getActiveCampaignsForClient($effectiveClientId)
            : $this->campaignService->getActiveCampaigns();
        
        // Only global users can see clients list (for filtering)
        $clients = $effectiveClientId ? collect() : $this->clientService->getActiveClients();

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
            },
            'assignee',
            'dispatchAttempts' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
        ]);

        // Convert resource to array directly to avoid serialization issues
        $leadData = (new LeadResource($lead))->toArray(request());

        // Get available users for manual assignment
        $availableUsers = User::select('id', 'name', 'email')->orderBy('name')->get();

        // Get close reason options
        $closeReasonOptions = LeadCloseReason::options();

        return Inertia::render('Leads/Show', [
            'lead' => $leadData,
            'available_users' => $availableUsers,
            'close_reason_options' => $closeReasonOptions,
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

    // ==========================================
    // STAGE & CLOSE ACTIONS
    // ==========================================

    /**
     * Close a lead with reason and notes.
     */
    public function close(CloseLeadRequest $request, string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        try {
            $this->stageService->closeLead(
                $lead,
                $request->getCloseReason(),
                $request->getCloseNotes()
            );

            return redirect()->back()
                ->with('success', 'Lead cerrado exitosamente');
        } catch (LeadStageException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update lead stage manually.
     */
    public function updateStage(UpdateLeadStageRequest $request, string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        try {
            $this->stageService->transitionTo(
                $lead,
                $request->getStage(),
                $request->getReason()
            );

            return redirect()->back()
                ->with('success', 'Stage actualizado exitosamente');
        } catch (LeadStageException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark lead as sales ready.
     */
    public function markSalesReady(Request $request, string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        try {
            $assignToUserId = $request->input('assign_to_user_id');
            $this->stageService->markSalesReady($lead, $assignToUserId);

            return redirect()->back()
                ->with('success', 'Lead marcado como Sales Ready');
        } catch (LeadStageException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reopen a closed lead.
     */
    public function reopen(string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        try {
            $this->stageService->reopenLead($lead);

            return redirect()->back()
                ->with('success', 'Lead reabierto exitosamente');
        } catch (LeadStageException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    // ==========================================
    // AUTOMATION ACTIONS
    // ==========================================

    /**
     * Start automation for a lead.
     */
    public function startAutomation(string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        try {
            $this->stageService->startAutomation($lead);

            return redirect()->back()
                ->with('success', 'Automatización iniciada');
        } catch (LeadStageException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Pause automation for a lead.
     */
    public function pauseAutomation(string $id): RedirectResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        try {
            $this->stageService->pauseAutomation($lead);

            return redirect()->back()
                ->with('success', 'Automatización pausada');
        } catch (LeadStageException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    // ==========================================
    // DISPATCH ACTIONS
    // ==========================================

    /**
     * Get dispatch attempts for a lead.
     */
    public function dispatchAttempts(string $id): JsonResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            abort(404, 'Lead no encontrado');
        }

        $attempts = $this->dispatchService->getAttemptsForLead($lead);

        return response()->json([
            'data' => LeadDispatchAttemptResource::collection($attempts),
        ]);
    }

    /**
     * Retry a failed dispatch attempt.
     */
    public function retryDispatch(string $attemptId): RedirectResponse
    {
        try {
            $attempt = \App\Models\Lead\LeadDispatchAttempt::findOrFail($attemptId);
            $this->dispatchService->retryDispatch($attempt);

            return redirect()->back()
                ->with('success', 'Reintento de dispatch iniciado');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al reintentar dispatch: ' . $e->getMessage());
        }
    }
}
