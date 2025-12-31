<?php

use App\Http\Controllers\Api\CallProviderWebhookController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WebhookEventController;
use App\Http\Controllers\Api\WebhookWhatsappController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AIRobot API Routes - External Webhooks Only
|--------------------------------------------------------------------------
|
| This file contains ONLY stateless external webhook endpoints.
| These endpoints receive data from external systems (n8n, telephony
| providers, WhatsApp, etc.) and are validated by token header.
|
| 📥 WEBHOOKS EXTERNOS (Sin autenticación, validados por token)
|    → /api/webhooks/* - Reciben datos de sistemas externos
|
| 🔐 PANEL API (Internal JSON endpoints)
|    → /panel-api/* - See routes/panel.php (uses web session auth)
|
| 📊 DOCUMENTACIÓN
|    → Scramble: http://localhost:8001/docs/api
|
*/

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║                      📥 WEBHOOKS EXTERNOS (Entrada)                      ║
// ║                                                                          ║
// ║  Reciben datos de sistemas externos (n8n, proveedores de telefonía,    ║
// ║  WhatsApp, etc.). Validados por token en header X-Webhook-Token.       ║
// ╚══════════════════════════════════════════════════════════════════════════╝

Route::prefix('webhooks')
    ->middleware([\App\Http\Middleware\ValidateWebhookSignature::class])
    ->name('webhooks.')
    ->group(function () {

        // ─────────────────────────────────────────────────────────────────────
        // 📞 LEADS - Ingreso de leads
        // ─────────────────────────────────────────────────────────────────────

        // Webhook directo para registrar lead
        Route::post('/lead', [WebhookController::class, 'receiveLead'])
            ->name('lead');

        // Webhook por eventos (Strategy pattern)
        Route::post('/event', [WebhookEventController::class, 'handleEvent'])
            ->name('event');

        // ─────────────────────────────────────────────────────────────────────
        // 📞 LLAMADAS - Proveedores de telefonía
        // ─────────────────────────────────────────────────────────────────────

        // Webhook genérico de llamadas (legacy)
        Route::post('/call', [WebhookController::class, 'receiveCall'])
            ->name('call');

        // Retell AI
        Route::post('/retell-call', [CallProviderWebhookController::class, 'retellWebhook'])
            ->name('retell.call');

        // Vapi
        Route::post('/vapi-call', [CallProviderWebhookController::class, 'vapiWebhook'])
            ->name('vapi.call');

        // Webhook genérico con provider dinámico
        Route::post('/call/{provider}', [CallProviderWebhookController::class, 'genericWebhook'])
            ->name('call.generic');

        // ─────────────────────────────────────────────────────────────────────
        // 💬 WHATSAPP - Mensajes entrantes
        // ─────────────────────────────────────────────────────────────────────

        Route::post('/whatsapp-incoming', [WebhookWhatsappController::class, 'incoming'])
            ->name('whatsapp.incoming');
    });

// Listar eventos disponibles (público, para debugging)
Route::get('/webhooks/events', [WebhookEventController::class, 'listEvents'])
    ->name('webhooks.events.list');


// ╔══════════════════════════════════════════════════════════════════════════╗
// ║                          🛡️ NOTAS DE SEGURIDAD                           ║
// ╚══════════════════════════════════════════════════════════════════════════╝

/*
|--------------------------------------------------------------------------
| 📥 WEBHOOKS EXTERNOS (/api/webhooks/*)
|--------------------------------------------------------------------------
|
| Authentication: Header X-Webhook-Token
| Configuration: .env → WEBHOOK_TOKEN=your_secret_token
| Generate token: php artisan webhook:generate-token --show
|
| Example:
| curl -X POST /api/webhooks/lead \
|   -H "X-Webhook-Token: your_token" \
|   -d '{"phone":"123","name":"Juan"}'
|
|--------------------------------------------------------------------------
| 🔐 PANEL API (/panel-api/*)
|--------------------------------------------------------------------------
|
| Authentication: Web session (cookies)
| Location: routes/panel.php
|
| These endpoints are for the admin panel's AJAX calls and use the same
| session authentication as the web routes. No Bearer tokens needed.
|
|--------------------------------------------------------------------------
| 🚦 RATE LIMITING
|--------------------------------------------------------------------------
|
| Add throttle middleware to webhooks:
| ->middleware('throttle:60,1') // 60 requests per minute
|
|--------------------------------------------------------------------------
| 📚 API DOCUMENTATION
|--------------------------------------------------------------------------
|
| Scramble OpenAPI: http://localhost:8001/docs/api
|
*/
