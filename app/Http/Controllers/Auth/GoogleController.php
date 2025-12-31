<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Integration\GoogleIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['email', 'profile', 'https://www.googleapis.com/auth/drive.file', 'https://www.googleapis.com/auth/spreadsheets'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $integration = GoogleIntegration::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                ],
                [
                    'google_id' => $googleUser->id,
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'avatar' => $googleUser->avatar,
                    'access_token' => $googleUser->token,
                    'refresh_token' => $googleUser->refreshToken,
                    'expires_in' => $googleUser->expiresIn,
                ]
            );

            return redirect()->route('settings.integrations')->with('success', 'Cuenta de Google conectada exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('settings.integrations')->with('error', 'Error al conectar con Google: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        GoogleIntegration::where('user_id', Auth::id())->delete();

        return redirect()->route('settings.integrations')->with('success', 'Cuenta de Google desconectada');
    }
}
