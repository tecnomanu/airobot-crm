<?php

namespace App\Http\Controllers\Web\Lead;

use App\Http\Controllers\Controller;
use App\Services\Campaign\CampaignService;
use App\Services\Lead\LeadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadIntencionController extends Controller
{
    public function __construct(
        private LeadService $leadService,
        private CampaignService $campaignService
    ) {}

    public function index(Request $request): Response
    {
        // Filtrar leads por fuentes que incluyen intención (whatsapp, agente_ia)
        $filters = [
            'source' => $request->input('source', 'whatsapp,agente_ia'),
            'campaign_id' => $request->input('campaign_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $leads = $this->leadService->getLeads($filters, $request->input('per_page', 15));
        $campaigns = $this->campaignService->getActiveCampaigns();

        return Inertia::render('LeadsIntencion/Index', [
            'leads' => $leads,
            'campaigns' => $campaigns,
            'filters' => $filters,
        ]);
    }
}
