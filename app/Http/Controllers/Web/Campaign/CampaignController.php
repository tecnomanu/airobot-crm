<?php

namespace App\Http\Controllers\Web\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\Source\SourceResource;
use App\Models\Client\Client;
use App\Models\Integration\GoogleIntegration;
use App\Services\Campaign\CampaignService;
use App\Services\Campaign\CampaignWhatsappTemplateService;
use App\Services\Client\ClientService;
use App\Services\Source\SourceService;
use App\Services\User\UserService;
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
        private SourceService $sourceService,
        private UserService $userService
    ) {}

    /**
     * Get available Google integrations for a campaign.
     *
     * Logic:
     * - Internal users: see internal integrations + campaign client's integrations
     * - Client users: see only their own client's integrations
     *
     * @param string|null $campaignClientId The client_id of the campaign being edited
     * @return array{integrations: array, can_change: bool, current_integration: ?array}
     */
    private function getAvailableGoogleIntegrations(?string $campaignClientId): array
    {
        $user = Auth::user();
        $isInternalUser = $user->client_id === Client::INTERNAL_CLIENT_ID;

        $integrations = collect();

        // Always include user's own client integrations
        if ($user->client_id) {
            $ownIntegrations = GoogleIntegration::where('client_id', $user->client_id)
                ->get()
                ->map(fn($i) => [
                    'id' => $i->id,
                    'email' => $i->email,
                    'client_id' => $i->client_id,
                    'is_internal' => $i->client_id === Client::INTERNAL_CLIENT_ID,
                    'label' => $i->client_id === Client::INTERNAL_CLIENT_ID
                        ? "AirRobot ({$i->email})"
                        : $i->email,
                ]);
            $integrations = $integrations->merge($ownIntegrations);
        }

        // Internal users also see the campaign client's integrations
        if ($isInternalUser && $campaignClientId && $campaignClientId !== Client::INTERNAL_CLIENT_ID) {
            $clientIntegrations = GoogleIntegration::where('client_id', $campaignClientId)
                ->get()
                ->map(fn($i) => [
                    'id' => $i->id,
                    'email' => $i->email,
                    'client_id' => $i->client_id,
                    'is_internal' => false,
                    'label' => $i->email,
                ]);
            $integrations = $integrations->merge($clientIntegrations);
        }

        return [
            'integrations' => $integrations->unique('id')->values()->toArray(),
            'can_change' => $isInternalUser || $integrations->isNotEmpty(),
            'user_is_internal' => $isInternalUser,
        ];
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Determine effective client_id for tenant isolation
        $isAdmin = $user->role->value === 'admin';
        $isSupervisor = $user->role->value === 'supervisor';
        $isGlobalUser = $isAdmin || ($isSupervisor && $user->client_id === null);
        $effectiveClientId = $isGlobalUser ? null : $user->client_id;

        $filters = [
            'client_id' => $request->input('client_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        // Force client_id filter for non-global users
        if ($effectiveClientId) {
            $filters['client_id'] = $effectiveClientId;
        }

        $campaigns = $this->campaignService->getCampaigns($filters, $request->input('per_page', 15));
        
        // Only global users can see clients list
        $clients = $effectiveClientId ? collect() : $this->clientService->getActiveClients();

        // Get WhatsApp sources filtered by client for non-global users
        $sourceFilters = ['active_only' => true];
        if ($effectiveClientId) {
            $sourceFilters['client_id'] = $effectiveClientId;
        }
        $allActiveSources = $this->sourceService->getAll($sourceFilters);
        $whatsappSources = $allActiveSources->filter(fn ($s) => $s->type->isWhatsApp());

        // Determine if user can manage campaigns
        $canManage = $isAdmin || $isSupervisor;

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'clients' => $clients,
            'filters' => $filters,
            'whatsapp_sources' => $whatsappSources->values()->map(fn ($s) => (new SourceResource($s))->resolve()),
            'can' => [
                'create' => $canManage,
                'edit' => $canManage,
                'delete' => $canManage,
            ],
        ]);
    }

    public function show(string $id): Response
    {
        $user = Auth::user();
        $campaign = $this->campaignService->getCampaignById($id);

        if (! $campaign) {
            abort(404, 'Campaña no encontrada');
        }

        // Tenant isolation: verify user can access this campaign
        $isAdmin = $user->role->value === 'admin';
        $isSupervisor = $user->role->value === 'supervisor';
        $isGlobalUser = $isAdmin || ($isSupervisor && $user->client_id === null);
        $effectiveClientId = $isGlobalUser ? null : $user->client_id;

        if ($effectiveClientId && $campaign->client_id !== $effectiveClientId) {
            abort(403, 'No tienes permiso para ver esta campaña');
        }

        // Load assignees with their user relation
        $campaign->load(['assignees.user', 'assignmentCursor']);

        $templates = $this->templateService->getTemplatesByCampaign($id);
        
        // Only global users can see clients list
        $clients = $effectiveClientId ? collect() : $this->clientService->getActiveClients();

        // Get sources filtered by client for non-global users
        $sourceFilters = ['active_only' => true];
        if ($effectiveClientId) {
            $sourceFilters['client_id'] = $effectiveClientId;
        }
        $allActiveSources = $this->sourceService->getAll($sourceFilters);

        // Filtrar por tipo (WhatsApp incluye Evolution API y Meta)
        $whatsappSources = $allActiveSources->filter(fn($s) => $s->type->isWhatsApp());
        $webhookSources = $allActiveSources->filter(fn($s) => $s->type->isWebhook());

        // Get available sellers for assignment (from campaign's client + global sellers)
        $availableUsers = $this->userService->getSellersForCampaign($campaign->client_id);

        // Get available Google integrations for this campaign
        $googleIntegrationsData = $this->getAvailableGoogleIntegrations($campaign->client_id);

        // Determine if user can manage this campaign
        $canManage = $isAdmin || $isSupervisor;

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'templates' => $templates,
            'clients' => $clients,
            'whatsapp_sources' => $whatsappSources->values()->map(fn($s) => (new SourceResource($s))->resolve()),
            'webhook_sources' => $webhookSources->values()->map(fn($s) => (new SourceResource($s))->resolve()),
            'available_users' => $availableUsers,
            'google_integrations' => $googleIntegrationsData,
            'can' => [
                'edit' => $canManage,
                'delete' => $canManage,
            ],
        ]);
    }

    public function create(): Response
    {
        $user = Auth::user();
        
        // Only admin/supervisor can create campaigns
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para crear campañas');
        }

        $clients = $this->clientService->getActiveClients();

        // Obtener todas las fuentes activas
        $allActiveSources = $this->sourceService->getAll(['active_only' => true]);

        // Filtrar por tipo
        $whatsappSources = $allActiveSources->filter(fn($s) => $s->type->isWhatsApp());
        $webhookSources = $allActiveSources->filter(fn($s) => $s->type->isWebhook());

        // Get available Google integrations (no campaign client yet, so just user's client)
        $googleIntegrationsData = $this->getAvailableGoogleIntegrations(null);

        return Inertia::render('Campaigns/Create', [
            'clients' => $clients,
            'whatsapp_sources' => $whatsappSources->values()->map(fn($s) => (new SourceResource($s))->resolve()),
            'webhook_sources' => $webhookSources->values()->map(fn($s) => (new SourceResource($s))->resolve()),
            'templates' => [],
            'google_integrations' => $googleIntegrationsData,
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $user = Auth::user();
        
        // Only admin/supervisor can create campaigns
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para crear campañas');
        }

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
        $user = Auth::user();
        
        // Only admin/supervisor can update campaigns
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para editar campañas');
        }

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
        $user = Auth::user();
        
        // Only admin/supervisor can delete campaigns
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para eliminar campañas');
        }

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
        $user = Auth::user();
        
        // Only admin/supervisor can toggle campaign status
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para cambiar el estado de campañas');
        }

        try {
            $this->campaignService->toggleCampaignStatus($id);

            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
