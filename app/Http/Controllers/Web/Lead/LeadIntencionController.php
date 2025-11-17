<?php

namespace App\Http\Controllers\Web\Lead;

use App\Http\Controllers\Controller;
use App\Http\Resources\Lead\LeadResource;
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

        // Eager load interactions para optimizar
        $leads = $this->leadService->getLeads($filters, $request->input('per_page', 15));
        
        // Cargar solo el último mensaje inbound para cada lead (optimizado)
        $leads->each(function ($lead) {
            $lead->loadCount('interactions');
            $lead->load(['interactions' => function ($query) {
                $query->where('direction', 'inbound')
                    ->orderBy('created_at', 'desc')
                    ->limit(1);
            }]);
        });
        
        $campaigns = $this->campaignService->getActiveCampaigns();

        return Inertia::render('LeadsIntencion/Index', [
            'leads' => LeadResource::collection($leads),
            'campaigns' => $campaigns,
            'filters' => $filters,
        ]);
    }

    /**
     * Mostrar detalle de intención de un lead con todas sus interacciones
     */
    public function show(string $id): Response
    {
        $lead = $this->leadService->getLeadById($id);

        if (! $lead) {
            abort(404);
        }

        // Cargar todas las interacciones ordenadas por fecha
        $lead->load(['interactions' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        return Inertia::render('LeadsIntencion/Show', [
            'lead' => new LeadResource($lead),
        ]);
    }
}
