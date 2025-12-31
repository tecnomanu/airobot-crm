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
            ],
        ];
    }
}
