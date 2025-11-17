<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\SourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Source\StoreSourceRequest;
use App\Http\Requests\Source\UpdateSourceRequest;
use App\Http\Resources\Source\SourceResource;
use App\Services\Client\ClientService;
use App\Services\Source\SourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador Web para gestión de Sources (Fuentes)
 * Controllers delgados → toda lógica en SourceService
 */
class SourceController extends Controller
{
    public function __construct(
        private SourceService $sourceService,
        private ClientService $clientService
    ) {}

    /**
     * Página de listado de fuentes
     */
    public function index(Request $request): Response
    {
        $filters = [
            'type' => $request->query('type'),
            'status' => $request->query('status'),
            'client_id' => $request->query('client_id'),
            'search' => $request->query('search'),
        ];

        $sources = $this->sourceService->paginate($filters, 15);
        $clients = $this->clientService->getActiveClients();

        // Agregar conteo de campañas a cada source usando through()
        $sources->through(function ($source) {
            // Contar campañas que usan este source en campaign_options
            $source->campaigns_count = \App\Models\CampaignOption::where('source_id', $source->id)
                ->distinct('campaign_id')
                ->count('campaign_id');

            return $source;
        });

        return Inertia::render('Sources/Index', [
            'sources' => SourceResource::collection($sources),
            'clients' => $clients,
            'filters' => $filters,
            'source_types' => collect(SourceType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    /**
     * Página de crear fuente
     */
    public function create(): Response
    {
        $clients = $this->clientService->getActiveClients();

        return Inertia::render('Sources/Form', [
            'source' => null,
            'clients' => $clients,
            'source_types' => collect(SourceType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'required_fields' => $type->requiredConfigFields(),
            ]),
        ]);
    }

    /**
     * Almacenar nueva fuente
     */
    public function store(StoreSourceRequest $request)
    {
        $this->sourceService->create(
            array_merge($request->validated(), [
                'created_by' => Auth::id(),
            ])
        );

        // Si viene desde una campaña, redirigir de vuelta
        if ($request->has('redirect_to') && $request->redirect_to) {
            return redirect($request->redirect_to)
                ->with('success', 'Fuente creada exitosamente');
        }

        return redirect()
            ->route('sources.index')
            ->with('success', 'Fuente creada exitosamente');
    }

    /**
     * Página de editar fuente
     */
    public function edit(int $id): Response
    {
        $source = $this->sourceService->getById($id);
        $clients = $this->clientService->getActiveClients();

        return Inertia::render('Sources/Form', [
            'source' => new SourceResource($source),
            'clients' => $clients,
            'source_types' => collect(SourceType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'required_fields' => $type->requiredConfigFields(),
            ]),
        ]);
    }

    /**
     * Actualizar fuente
     */
    public function update(UpdateSourceRequest $request, int $id)
    {
        $this->sourceService->update($id, $request->validated());

        // Si viene desde una campaña, redirigir de vuelta
        if ($request->has('redirect_to') && $request->redirect_to) {
            return redirect($request->redirect_to)
                ->with('success', 'Fuente actualizada exitosamente');
        }

        return redirect()
            ->route('sources.index')
            ->with('success', 'Fuente actualizada exitosamente');
    }

    /**
     * Eliminar fuente
     */
    public function destroy(int $id)
    {
        $this->sourceService->delete($id);

        return redirect()
            ->route('sources.index')
            ->with('success', 'Fuente eliminada exitosamente');
    }
}
