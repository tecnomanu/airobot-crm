<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Lead\LeadCallService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Web Controller for Lead Calls (replaces CallHistoryController)
 */
class LeadCallController extends Controller
{
    public function __construct(
        private LeadCallService $leadCallService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['campaign_id', 'status', 'from', 'to', 'search']);
        $perPage = $request->input('per_page', 15);

        $calls = $this->leadCallService->getCallHistory($filters, $perPage);

        return Inertia::render('LeadCall/Index', [
            'calls' => $calls,
            'filters' => $filters,
        ]);
    }

    public function show(string $id): Response
    {
        $call = $this->leadCallService->getCallById($id);

        if (! $call) {
            abort(404);
        }

        return Inertia::render('LeadCall/Show', [
            'call' => $call,
        ]);
    }
}

