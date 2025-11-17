<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Webhook-Token');
        $expectedToken = config('services.whatsapp.webhook_token', env('WHATSAPP_WEBHOOK_TOKEN'));

        if (! $token || $token !== $expectedToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid webhook token',
            ], 401);
        }

        return $next($request);
    }
}
