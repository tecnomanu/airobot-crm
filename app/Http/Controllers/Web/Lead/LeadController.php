<?php

namespace App\Http\Controllers\Web\Lead;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Resources\Lead\LeadResource;
use App\Services\Campaign\CampaignService;
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
        private CampaignService $campaignService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'campaign_id' => $request->input('campaign_id'),
            'status' => $request->input('status'),
            'source' => $request->input('source'),
            'search' => $request->input('search'),
        ];

        $leads = $this->leadService->getLeads($filters, $request->input('per_page', 15));
        $campaigns = $this->campaignService->getActiveCampaigns();

        return Inertia::render('Leads/Index', [
            'leads' => LeadResource::collection($leads),
            'campaigns' => $campaigns,
            'filters' => $filters,
        ]);
    }

    public function show(string $id): Response
    {
        $lead = $this->leadService->getLeadById($id);

        if (! $lead) {
            abort(404, 'Lead no encontrado');
        }

        // Cargar interacciones para mostrar el historial completo
        $lead->load(['interactions' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return Inertia::render('Leads/Show', [
            'lead' => new LeadResource($lead),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        try {
            $this->leadService->createLead(
                array_merge($request->validated(), [
                    'created_by' => Auth::id(),
                ])
            );

            return redirect()->route('leads.index')
                ->with('success', 'Lead creado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function update(UpdateLeadRequest $request, string $id): RedirectResponse
    {
        try {
            $this->leadService->updateLead($id, $request->validated());

            return redirect()->route('leads.index')
                ->with('success', 'Lead actualizado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

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

    public function retryAutomationBatch(Request $request): RedirectResponse
    {
        try {
            $filters = [
                'campaign_id' => $request->input('campaign_id'),
                'option_selected' => $request->input('option_selected'),
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
}
