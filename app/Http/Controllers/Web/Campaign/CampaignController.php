<?php

namespace App\Http\Controllers\Web\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\Source\SourceResource;
use App\Services\Campaign\CampaignService;
use App\Services\Campaign\CampaignWhatsappTemplateService;
use App\Services\Client\ClientService;
use App\Services\Source\SourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService,
        private ClientService $clientService,
        private CampaignWhatsappTemplateService $templateService,
        private SourceService $sourceService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'client_id' => $request->input('client_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $campaigns = $this->campaignService->getCampaigns($filters, $request->input('per_page', 15));
        $clients = $this->clientService->getActiveClients();

        // Get WhatsApp sources for campaign creation
        $allActiveSources = $this->sourceService->getAll(['active_only' => true]);
        $whatsappSources = $allActiveSources->filter(fn ($s) => $s->type->isWhatsApp());

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'clients' => $clients,
            'filters' => $filters,
            'whatsapp_sources' => $whatsappSources->values()->map(fn ($s) => (new SourceResource($s))->resolve()),
        ]);
    }

    public function show(string $id): Response
    {
        $campaign = $this->campaignService->getCampaignById($id);

        if (! $campaign) {
            abort(404, 'Campaña no encontrada');
        }

        $templates = $this->templateService->getTemplatesByCampaign($id);
        $clients = $this->clientService->getActiveClients();

        // Obtener todas las fuentes activas
        $allActiveSources = $this->sourceService->getAll(['active_only' => true]);

        // Filtrar por tipo (WhatsApp incluye Evolution API y Meta)
        $whatsappSources = $allActiveSources->filter(fn($s) => $s->type->isWhatsApp());
        $webhookSources = $allActiveSources->filter(fn($s) => $s->type->isWebhook());

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'templates' => $templates,
            'clients' => $clients,
            'whatsapp_sources' => $whatsappSources->values()->map(fn($s) => (new SourceResource($s))->resolve()),
            'webhook_sources' => $webhookSources->values()->map(fn($s) => (new SourceResource($s))->resolve()),
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        try {
            $this->campaignService->createCampaign(
                array_merge($request->validated(), [
                    'created_by' => Auth::id(),
                ])
            );

            return redirect()->route('campaigns.index')
                ->with('success', 'Campaña creada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function update(UpdateCampaignRequest $request, string $id): RedirectResponse
    {
        try {
            $this->campaignService->updateCampaign($id, $request->validated());

            return redirect()->back()
                ->with('success', 'Campaña actualizada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        try {
            $this->campaignService->deleteCampaign($id);

            return redirect()->route('campaigns.index')
                ->with('success', 'Campaña eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function toggleStatus(string $id): RedirectResponse
    {
        try {
            $this->campaignService->toggleCampaignStatus($id);

            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
