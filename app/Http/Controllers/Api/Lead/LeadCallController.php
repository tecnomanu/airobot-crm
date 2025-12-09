<?php

namespace App\Http\Controllers\Api\Lead;

use App\Http\Controllers\Controller;
use App\Http\Resources\Lead\LeadCallResource;
use App\Http\Traits\ApiResponse;
use App\Services\Lead\LeadCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for Lead Calls (replaces CallHistoryController)
 */
class LeadCallController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LeadCallService $leadCallService
    ) {}

    /**
     * List lead calls with filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['campaign_id', 'lead_id', 'client_id', 'status', 'from', 'to']);
        $perPage = $request->input('per_page', 15);

        $calls = $this->leadCallService->getCallsWithFilters($filters, $perPage);

        return $this->successResponse(
            LeadCallResource::collection($calls),
            'Lead calls retrieved successfully'
        );
    }

    /**
     * Show a specific lead call
     */
    public function show(string $id): JsonResponse
    {
        $call = $this->leadCallService->getCallById($id);

        if (! $call) {
            return $this->notFoundResponse('Lead call not found');
        }

        return $this->successResponse(
            new LeadCallResource($call),
            'Lead call retrieved successfully'
        );
    }
}

