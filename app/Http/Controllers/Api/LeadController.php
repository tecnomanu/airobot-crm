<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Resources\Lead\LeadCollection;
use App\Http\Resources\Lead\LeadResource;
use App\Http\Resources\LeadInteractionResource;
use App\Http\Traits\ApiResponse;
use App\Services\Lead\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LeadService $leadService
    ) {}

    /**
     * Listar leads con filtros
     */
    public function index(Request $request): LeadCollection
    {
        $filters = $request->only(['campaign_id', 'status', 'source', 'search']);
        $perPage = $request->input('per_page', 15);

        $leads = $this->leadService->getLeads($filters, $perPage);

        return new LeadCollection($leads);
    }

    /**
     * Mostrar un lead específico
     */
    public function show(string $id): JsonResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (! $lead) {
            return $this->notFoundResponse('Lead not found');
        }

        return $this->successResponse(
            new LeadResource($lead),
            'Lead retrieved successfully'
        );
    }

    /**
     * Crear un nuevo lead
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        try {
            $lead = $this->leadService->createLead(
                array_merge($request->validated(), [
                    'created_by' => auth()->id(),
                ])
            );

            return $this->createdResponse(
                new LeadResource($lead),
                'Lead created successfully'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Validation error', $e->getMessage(), 400);
        }
    }

    /**
     * Actualizar un lead existente
     */
    public function update(UpdateLeadRequest $request, string $id): JsonResponse
    {
        try {
            $lead = $this->leadService->updateLead($id, $request->validated());

            return $this->updatedResponse(
                new LeadResource($lead),
                'Lead updated successfully'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Eliminar un lead
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->leadService->deleteLead($id);

            return $this->deletedResponse('Lead deleted successfully');

        } catch (\InvalidArgumentException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Obtener timeline de interacciones del lead
     */
    public function interactions(string $id): JsonResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (! $lead) {
            return $this->notFoundResponse('Lead not found');
        }

        $interactions = $lead->interactions()
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse(
            LeadInteractionResource::collection($interactions),
            'Lead interactions retrieved successfully'
        );
    }
}
