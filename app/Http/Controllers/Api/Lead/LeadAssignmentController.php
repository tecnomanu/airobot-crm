<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Lead;

use App\Http\Controllers\Controller;
use App\Http\Resources\Lead\LeadResource;
use App\Models\Lead\Lead;
use App\Services\Lead\LeadAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadAssignmentController extends Controller
{
    public function __construct(
        private LeadAssignmentService $assignmentService
    ) {}

    /**
     * Manually assign a lead to a user.
     */
    public function assign(Request $request, string $leadId): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $lead = Lead::findOrFail($leadId);

        $success = $this->assignmentService->assignManually(
            $lead,
            $validated['user_id'],
            Auth::id()
        );

        if (! $success) {
            return response()->json([
                'message' => 'Failed to assign lead',
            ], 422);
        }

        $lead->load('assignee');

        return response()->json([
            'message' => 'Lead assigned successfully',
            'lead' => new LeadResource($lead),
        ]);
    }

    /**
     * Unassign a lead.
     */
    public function unassign(string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $this->assignmentService->unassign($lead, Auth::id());

        return response()->json([
            'message' => 'Lead unassigned successfully',
            'lead' => new LeadResource($lead),
        ]);
    }

    /**
     * Retry auto-assignment for a lead that failed.
     */
    public function retryAssignment(string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $success = $this->assignmentService->assignOnSalesReady($lead);

        $lead->refresh();
        $lead->load('assignee');

        if (! $success) {
            return response()->json([
                'message' => 'Assignment failed: ' . ($lead->assignment_error ?? 'No assignees configured'),
                'lead' => new LeadResource($lead),
            ], 422);
        }

        return response()->json([
            'message' => 'Lead assigned successfully',
            'lead' => new LeadResource($lead),
        ]);
    }
}

