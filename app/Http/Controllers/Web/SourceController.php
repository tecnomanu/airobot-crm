<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\SourceType;
use App\Exceptions\Business\ValidationException as BusinessValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Source\StoreSourceRequest;
use App\Http\Requests\Source\UpdateSourceRequest;
use App\Http\Resources\Source\SourceResource;
use App\Models\Campaign\CampaignOption;
use App\Models\Integration\Source;
use App\Services\Client\ClientService;
use App\Services\Source\SourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
        $user = Auth::user();
        
        // Determine effective client_id for tenant isolation
        $isAdmin = $user->role->value === 'admin';
        $isSupervisor = $user->role->value === 'supervisor';
        $isGlobalUser = $isAdmin || ($isSupervisor && $user->client_id === null);
        $effectiveClientId = $isGlobalUser ? null : $user->client_id;

        $filters = [
            'type' => $request->query('type'),
            'status' => $request->query('status'),
            'client_id' => $request->query('client_id'),
            'search' => $request->query('search'),
        ];

        // Force client_id filter for non-global users
        if ($effectiveClientId) {
            $filters['client_id'] = $effectiveClientId;
        }

        $sources = $this->sourceService->paginate($filters, 15);
        
        // Only global users can see clients list
        $clients = $effectiveClientId ? collect() : $this->clientService->getActiveClients();

        // Agregar conteo de campañas a cada source usando through()
        $sources->through(function ($source) {
            // Contar campañas que usan este source en campaign_options
            $source->campaigns_count = CampaignOption::where('source_id', $source->id)
                ->distinct('campaign_id')
                ->count('campaign_id');

            return $source;
        });

        // Determine if user can manage sources
        $canManage = $isAdmin || $isSupervisor;

        return Inertia::render('Sources/Index', [
            'sources' => SourceResource::collection($sources),
            'clients' => $clients,
            'filters' => $filters,
            'source_types' => collect(SourceType::cases())->map(fn($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
            'can' => [
                'create' => $canManage,
                'edit' => $canManage,
                'delete' => $canManage,
            ],
        ]);
    }

    /**
     * Página de crear fuente
     */
    public function create(): Response
    {
        $user = Auth::user();
        
        // Only admin/supervisor can create sources
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para crear fuentes');
        }

        $clients = $this->clientService->getActiveClients();

        return Inertia::render('Sources/Form', [
            'source' => null,
            'clients' => $clients,
            'source_types' => collect(SourceType::cases())->map(fn($type) => [
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
        $user = Auth::user();
        
        // Only admin/supervisor can create sources
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para crear fuentes');
        }

        try {
            $this->sourceService->create(
                array_merge($request->validated(), [
                    'created_by' => Auth::id(),
                ])
            );
        } catch (BusinessValidationException $e) {
            throw ValidationException::withMessages([
                'config' => $e->getMessage(),
            ]);
        }

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
    public function edit(string $id): Response
    {
        $user = Auth::user();
        
        // Only admin/supervisor can edit sources
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para editar fuentes');
        }

        $source = $this->sourceService->getById($id);
        $clients = $this->clientService->getActiveClients();

        return Inertia::render('Sources/Form', [
            'source' => new SourceResource($source),
            'clients' => $clients,
            'source_types' => collect(SourceType::cases())->map(fn($type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'required_fields' => $type->requiredConfigFields(),
            ]),
        ]);
    }

    /**
     * Actualizar fuente
     */
    public function update(UpdateSourceRequest $request, string $id)
    {
        $user = Auth::user();
        
        // Only admin/supervisor can update sources
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para editar fuentes');
        }

        try {
            $this->sourceService->update($id, $request->validated());
        } catch (BusinessValidationException $e) {
            throw ValidationException::withMessages([
                'config' => $e->getMessage(),
            ]);
        }

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
    public function destroy(string $id)
    {
        $user = Auth::user();
        
        // Only admin/supervisor can delete sources
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para eliminar fuentes');
        }

        $this->sourceService->delete($id);

        return redirect()
            ->route('sources.index')
            ->with('success', 'Fuente eliminada exitosamente');
    }

    public function toggleStatus(Source $source, Request $request)
    {
        $user = Auth::user();
        
        // Only admin/supervisor can toggle source status
        if (!in_array($user->role->value, ['admin', 'supervisor'])) {
            abort(403, 'No tienes permiso para cambiar el estado de fuentes');
        }

        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $source->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Estado actualizado correctamente.');
    }
}
