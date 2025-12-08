<?php

namespace App\Http\Controllers\Api\Lead;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Http\Resources\Lead\LeadActivityResource;
use App\Http\Resources\Lead\LeadCollection;
use App\Http\Resources\Lead\LeadResource;
use App\Http\Traits\ApiResponse;
use App\Services\Lead\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LeadService $leadService
    ) {}

    /**
     * List leads with filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['campaign_id', 'status', 'source', 'search']);
        $perPage = $request->input('per_page', 15);

        $leads = $this->leadService->getLeads($filters, $perPage);

        return $this->successResponse(
            new LeadCollection($leads),
            'Leads retrieved successfully'
        );
    }

    /**
     * Show a specific lead
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
     * Create a new lead
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        try {
            $lead = $this->leadService->createLead(
                array_merge($request->validated(), [
                    'created_by' => Auth::id(),
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
     * Update an existing lead
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
     * Delete a lead
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
     * Get lead activity timeline (calls, messages, etc.)
     */
    public function activities(string $id): JsonResponse
    {
        $lead = $this->leadService->getLeadById($id);

        if (! $lead) {
            return $this->notFoundResponse('Lead not found');
        }

        $activities = $lead->activities()
            ->with('subject')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse(
            LeadActivityResource::collection($activities),
            'Lead activities retrieved successfully'
        );
    }

    /**
     * @deprecated Use activities() instead
     */
    public function interactions(string $id): JsonResponse
    {
        return $this->activities($id);
    }
}
