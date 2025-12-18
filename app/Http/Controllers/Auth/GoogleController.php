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
                    'google_id' => $googleUser->id,
                    'user_id' => Auth::id(),
                ],
                [
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                    'avatar' => $googleUser->avatar,
                    'access_token' => $googleUser->token,
                    'refresh_token' => $googleUser->refreshToken,
                    'expires_in' => $googleUser->expiresIn,
                ]
            );

            return redirect()->route('dashboard')->with('success', 'Cuenta de Google conectada exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Error al conectar con Google: ' . $e->getMessage());
        }
    }
}
