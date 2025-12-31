<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Models\Campaign\Campaign;
use App\Models\User;
use App\Services\Lead\LeadAssignmentService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignAssigneeController extends Controller
{
    public function __construct(
        private LeadAssignmentService $assignmentService,
        private UserService $userService
    ) {}

    /**
     * Get assignees for a campaign.
     */
    public function index(string $campaignId): JsonResponse
    {
        $campaign = Campaign::with(['assignees.user', 'assignmentCursor'])
            ->findOrFail($campaignId);

        return response()->json([
            'assignees' => $campaign->assignees->map(fn ($a) => [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'user' => [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                    'email' => $a->user->email,
                ],
                'is_active' => $a->is_active,
                'sort_order' => $a->sort_order,
            ]),
            'cursor' => $campaign->assignmentCursor ? [
                'current_index' => $campaign->assignmentCursor->current_index,
                'last_assigned_at' => $campaign->assignmentCursor->last_assigned_at?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Sync assignees for a campaign.
     * Only sellers from the campaign's client (or global sellers) can be assigned.
     */
    public function sync(Request $request, string $campaignId): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $campaign = Campaign::findOrFail($campaignId);

        // Validate that all provided users are valid sellers for this campaign
        $validSellers = $this->userService->getSellersForCampaign($campaign->client_id);
        $validSellerIds = $validSellers->pluck('id')->toArray();

        $invalidIds = array_diff($validated['user_ids'], $validSellerIds);
        if (!empty($invalidIds)) {
            return response()->json([
                'message' => 'Algunos usuarios no son vendedores válidos para este cliente',
                'invalid_ids' => array_values($invalidIds),
            ], 422);
        }

        $this->assignmentService->syncAssignees($campaign, $validated['user_ids']);

        return response()->json([
            'message' => 'Assignees updated successfully',
            'count' => count($validated['user_ids']),
        ]);
    }

    /**
     * Get available users that can be assigned to campaigns.
     * Optionally filter by campaign's client_id.
     */
    public function availableUsers(Request $request): JsonResponse
    {
        $clientId = $request->input('client_id');

        // Get sellers for the specific client (or all sellers if no client specified)
        $users = $this->userService->getSellersForCampaign($clientId);

        return response()->json(['users' => $users]);
    }
}

