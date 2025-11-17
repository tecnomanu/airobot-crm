<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Services\Client\ClientService;
use App\Services\Reporting\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService,
        private ReportingService $reportingService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $clients = $this->clientService->getClients($filters, $request->input('per_page', 15));

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $client = $this->clientService->getClientById($id);

        if (! $client) {
            abort(404, 'Cliente no encontrado');
        }

        // Overview general del cliente (todas las métricas)
        $overview = $this->reportingService->getClientOverview($client);

        // Resumen mensual (por defecto, mes actual)
        $month = $request->input('month', now()->format('Y-m'));
        $from = Carbon::parse($month.'-01')->startOfMonth();
        $to = Carbon::parse($month.'-01')->endOfMonth();

        $monthlySummary = $this->reportingService->getClientMonthlySummary($client, $from, $to);

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'overview' => $overview,
            'monthlySummary' => $monthlySummary->toArray(),
            'selectedMonth' => $month,
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        try {
            $this->clientService->createClient(
                array_merge($request->validated(), [
                    'created_by' => auth()->id(),
                ])
            );

            return redirect()->route('clients.index')
                ->with('success', 'Cliente creado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function update(UpdateClientRequest $request, string $id): RedirectResponse
    {
        try {
            $this->clientService->updateClient($id, $request->validated());

            return redirect()->route('clients.index')
                ->with('success', 'Cliente actualizado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        try {
            $this->clientService->deleteClient($id);

            return redirect()->route('clients.index')
                ->with('success', 'Cliente eliminado exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
