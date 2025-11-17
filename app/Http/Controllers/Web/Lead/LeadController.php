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

        return Inertia::render('Leads/Show', [
            'lead' => $lead,
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        try {
            $this->leadService->createLead(
                array_merge($request->validated(), [
                    'created_by' => auth()->id(),
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
}
