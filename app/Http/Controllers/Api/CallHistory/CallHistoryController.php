<?php

namespace App\Http\Controllers\Api\CallHistory;

use App\Http\Controllers\Controller;
use App\Http\Resources\CallHistory\CallHistoryCollection;
use App\Http\Resources\CallHistory\CallHistoryResource;
use App\Http\Traits\ApiResponse;
use App\Services\CallHistory\CallHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallHistoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CallHistoryService $callHistoryService
    ) {}

    /**
     * Listar historial de llamadas con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'client_id',
            'campaign_id',
            'status',
            'start_date',
            'end_date',
            'search',
        ]);
        $perPage = $request->input('per_page', 15);

        $calls = $this->callHistoryService->getCallHistory($filters, $perPage);

        return $this->successResponse(
            new CallHistoryCollection($calls),
            'Call history retrieved successfully'
        );
    }

    /**
     * Mostrar una llamada específica
     */
    public function show(string $id): JsonResponse
    {
        $call = $this->callHistoryService->getCallById($id);

        if (! $call) {
            return $this->notFoundResponse('Call history not found');
        }

        return $this->successResponse(
            new CallHistoryResource($call),
            'Call history retrieved successfully'
        );
    }
}
