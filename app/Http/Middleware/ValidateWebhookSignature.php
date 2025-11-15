<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookSignature
{
    /**
     * Handle an incoming request.
     * 
     * Valida webhooks entrantes usando:
     * 1. Token simple en header (X-Webhook-Token)
     * 2. Firma HMAC en header (X-Webhook-Signature)
     * 
     * Configuración en .env:
     * WEBHOOK_TOKEN=tu_token_secreto
     * WEBHOOK_SECRET=tu_secret_para_hmac
     * WEBHOOK_VALIDATION_ENABLED=true
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si la validación está deshabilitada, permitir
        if (!config('webhooks.validation_enabled', true)) {
            return $next($request);
        }

        $validationMethod = config('webhooks.validation_method', 'token'); // token o hmac

        if ($validationMethod === 'hmac') {
            return $this->validateHmac($request, $next);
        }

        return $this->validateToken($request, $next);
    }

    /**
     * Validar usando token simple
     */
    private function validateToken(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Webhook-Token');
        $expectedToken = config('webhooks.token');

        // Si no hay token configurado, permitir (pero logear advertencia)
        if (empty($expectedToken)) {
            Log::warning('Webhook token no configurado, permitiendo acceso', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return $next($request);
        }

        // Validar token
        if (empty($token) || !hash_equals($expectedToken, $token)) {
            Log::warning('Webhook con token inválido rechazado', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'provided_token' => $token ? 'present' : 'missing',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Invalid webhook token',
            ], 401);
        }

        return $next($request);
    }

    /**
     * Validar usando firma HMAC
     * 
     * El servidor externo debe enviar:
     * X-Webhook-Signature: sha256=<hash_hmac_sha256(body, secret)>
     */
    private function validateHmac(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Webhook-Signature');
        $secret = config('webhooks.secret');

        if (empty($secret)) {
            Log::warning('Webhook secret no configurado, permitiendo acceso', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return $next($request);
        }

        if (empty($signature)) {
            Log::warning('Webhook sin firma rechazado', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Missing webhook signature',
            ], 401);
        }

        // Calcular firma esperada
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        // Comparar firmas de forma segura
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Webhook con firma inválida rechazado', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Invalid webhook signature',
            ], 401);
        }

        return $next($request);
    }
}
