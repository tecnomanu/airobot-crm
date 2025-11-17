<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CallHistory\CallHistoryService;
use App\Services\Campaign\CampaignService;
use App\Services\Client\ClientService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CallHistoryController extends Controller
{
    public function __construct(
        private CallHistoryService $callHistoryService,
        private ClientService $clientService,
        private CampaignService $campaignService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'client_id' => $request->input('client_id'),
            'campaign_id' => $request->input('campaign_id'),
            'status' => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
        ];

        $calls = $this->callHistoryService->getCallHistory($filters, $request->input('per_page', 15));
        $clients = $this->clientService->getActiveClients();
        $campaigns = $this->campaignService->getActiveCampaigns();

        return Inertia::render('CallHistory/Index', [
            'calls' => $calls,
            'clients' => $clients,
            'campaigns' => $campaigns,
            'filters' => $filters,
        ]);
    }

    public function show(string $id): Response
    {
        $call = $this->callHistoryService->getCallById($id);

        if (! $call) {
            abort(404, 'Llamada no encontrada');
        }

        return Inertia::render('CallHistory/Show', [
            'call' => $call,
        ]);
    }
}
