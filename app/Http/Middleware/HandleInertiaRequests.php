<?php

namespace App\Http\Middleware;

use App\Models\Integration\GoogleIntegration;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        // Disable version check in development to prevent 409 conflicts with Vite HMR
        if (app()->environment('local')) {
            return null;
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Get Google integration for the user's client (tenant-scoped)
        $googleIntegration = null;
        if ($user?->client_id) {
            $googleIntegration = GoogleIntegration::where('client_id', $user->client_id)->first();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'google_integration' => $googleIntegration,
                'permissions' => $user ? $this->getUserPermissions($user) : [],
            ],
        ];
    }

    /**
     * Build permissions array for frontend navigation and access control.
     * 
     * Admin users are ALWAYS global regardless of client_id assignment.
     */
    private function getUserPermissions($user): array
    {
        $isAdmin = $user->role->value === 'admin';
        $isSupervisor = $user->role->value === 'supervisor';

        // Admins are always global, supervisors without client are global
        $isGlobal = $isAdmin || ($isSupervisor && $user->client_id === null);

        // Effective client_id for tenant scoping (null for global users)
        $effectiveClientId = $isGlobal ? null : $user->client_id;

        return [
            'is_global_user' => $isGlobal,
            'is_admin' => $isAdmin,
            'is_supervisor' => $isSupervisor,
            'effective_client_id' => $effectiveClientId,
            // Menu visibility permissions
            'can_view_clients' => $isAdmin || ($isSupervisor && !$effectiveClientId),
            'can_view_retell_agents' => $isAdmin || ($isSupervisor && !$effectiveClientId),
            'can_view_users' => $isAdmin || $isSupervisor,
            'can_view_integrations' => $isAdmin || !$effectiveClientId,
            'can_view_call_history' => true,
            'can_view_calculator' => $isAdmin || !$effectiveClientId,
        ];
    }
}
