<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Integration\GoogleIntegration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        $user = Auth::user();

        // User must have a client to connect integrations
        if (! $user->client_id) {
            return redirect()->route('settings.integrations')
                ->with('error', 'No tienes un cliente asignado para conectar integraciones.');
        }

        return Socialite::driver('google')
            ->scopes(['email', 'profile', 'https://www.googleapis.com/auth/drive.file', 'https://www.googleapis.com/auth/spreadsheets'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function callback()
    {
        $user = Auth::user();

        if (! $user->client_id) {
            return redirect()->route('settings.integrations')
                ->with('error', 'No tienes un cliente asignado para conectar integraciones.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            // Integration is owned by the client (tenant), not the user
            // One Google account per client (can reconnect/change account)
            $integration = GoogleIntegration::updateOrCreate(
                [
                    'client_id' => $user->client_id,
                ],
                [
                    'created_by_user_id' => $user->id,
                    'google_id' => $googleUser->id,
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'avatar' => $googleUser->avatar,
                    'access_token' => $googleUser->token,
                    'refresh_token' => $googleUser->refreshToken,
                    'expires_in' => $googleUser->expiresIn,
                ]
            );

            return redirect()->route('settings.integrations')
                ->with('success', 'Cuenta de Google conectada exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('settings.integrations')
                ->with('error', 'Error al conectar con Google: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $user = Auth::user();

        $integration = GoogleIntegration::where('client_id', $user->client_id)->first();

        if (! $integration) {
            return redirect()->route('settings.integrations')
                ->with('error', 'No hay integración de Google para desconectar.');
        }

        // Check policy authorization
        Gate::authorize('delete', $integration);

        $integration->delete();

        return redirect()->route('settings.integrations')
            ->with('success', 'Cuenta de Google desconectada');
    }
}
