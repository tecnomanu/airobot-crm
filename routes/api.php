<?php

use App\Http\Controllers\Api\CallHistoryController;
use App\Http\Controllers\Api\CallProviderWebhookController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientDispatchController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ReportingController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WebhookEventController;
use App\Http\Controllers\Api\WebhookWhatsappController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AIRobot API Routes
|--------------------------------------------------------------------------
|
| Estructura clara y organizada:
|
| 📥 WEBHOOKS EXTERNOS (Sin autenticación, validados por token)
|    → /api/webhooks/* - Reciben datos de sistemas externos
|
| 🔐 API ADMINISTRATIVA (Requiere Sanctum)
|    → /api/admin/* - Panel administrativo y operaciones internas
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
// ║                    🔐 API ADMINISTRATIVA (Protegida)                     ║
// ║                                                                          ║
// ║  Operaciones internas del panel administrativo.                        ║
// ║  Requiere autenticación con Sanctum (Bearer Token).                    ║
// ╚══════════════════════════════════════════════════════════════════════════╝

Route::prefix('admin')
    ->middleware(['auth:sanctum'])
    ->name('api.admin.')
    ->group(function () {

        // ─────────────────────────────────────────────────────────────────────
        // 👥 LEADS - Gestión de leads
        // ─────────────────────────────────────────────────────────────────────

        Route::apiResource('leads', LeadController::class);

        // ─────────────────────────────────────────────────────────────────────
        // 📢 CAMPAÑAS - Gestión de campañas y templates
        // ─────────────────────────────────────────────────────────────────────

        Route::apiResource('campaigns', CampaignController::class);

        // Templates de WhatsApp por campaña
        Route::prefix('campaigns/{campaignId}')->name('campaigns.')->group(function () {
            Route::get('templates', [CampaignController::class, 'getTemplates'])
                ->name('templates.index');
            Route::post('templates', [CampaignController::class, 'storeTemplate'])
                ->name('templates.store');
            Route::put('templates/{templateId}', [CampaignController::class, 'updateTemplate'])
                ->name('templates.update');
            Route::delete('templates/{templateId}', [CampaignController::class, 'destroyTemplate'])
                ->name('templates.destroy');
        });

        // ─────────────────────────────────────────────────────────────────────
        // 🏢 CLIENTES - Gestión de clientes
        // ─────────────────────────────────────────────────────────────────────

        Route::apiResource('clients', ClientController::class);

        // Dispatch de leads a cliente
        Route::prefix('clients/{client}')->name('clients.')->group(function () {
            Route::post('leads/{lead}/dispatch', [ClientDispatchController::class, 'dispatch'])
                ->name('leads.dispatch');
            Route::get('leads/{lead}/dispatch-status', [ClientDispatchController::class, 'status'])
                ->name('leads.dispatch.status');
        });

        // ─────────────────────────────────────────────────────────────────────
        // 📞 HISTORIAL DE LLAMADAS - Solo lectura
        // ─────────────────────────────────────────────────────────────────────

        Route::prefix('call-history')->name('call-history.')->group(function () {
            Route::get('/', [CallHistoryController::class, 'index'])->name('index');
            Route::get('/{id}', [CallHistoryController::class, 'show'])->name('show');
        });

        // ─────────────────────────────────────────────────────────────────────
        // 📊 REPORTES Y MÉTRICAS
        // ─────────────────────────────────────────────────────────────────────

        Route::prefix('reporting')->name('reporting.')->group(function () {
            // Métricas globales del dashboard
            Route::get('metrics', [ReportingController::class, 'globalMetrics'])
                ->name('metrics');

            // Rendimiento de campañas
            Route::get('campaigns/performance', [ReportingController::class, 'campaignPerformance'])
                ->name('campaigns.performance');

            // Reportes por cliente
            Route::get('clients/{client}/overview', [ReportingController::class, 'clientOverview'])
                ->name('clients.overview');
            Route::get('clients/{client}/monthly-summary', [ReportingController::class, 'clientMonthlySummary'])
                ->name('clients.monthly');
        });
    });

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║                          🛡️ NOTAS DE SEGURIDAD                           ║
// ╚══════════════════════════════════════════════════════════════════════════╝

/*
|--------------------------------------------------------------------------
| 1. 📥 WEBHOOKS EXTERNOS (/api/webhooks/*)
|--------------------------------------------------------------------------
|
| Autenticación: Header X-Webhook-Token
| Configuración: .env → WEBHOOK_TOKEN=tu_token_secreto
| Generar token: php artisan webhook:generate-token --show
| 
| Ejemplo:
| curl -X POST /api/webhooks/lead \
|   -H "X-Webhook-Token: tu_token" \
|   -d '{"phone":"123","name":"Juan"}'
|
|--------------------------------------------------------------------------
| 2. 🔐 API ADMINISTRATIVA (/api/admin/*)
|--------------------------------------------------------------------------
|
| Autenticación: Laravel Sanctum (Bearer Token)
| Generar token: $user->createToken('panel-admin')->plainTextToken
| Header: Authorization: Bearer {token}
|
| Ejemplo:
| curl -X GET /api/admin/leads \
|   -H "Authorization: Bearer 1|abc123..."
|
|--------------------------------------------------------------------------
| 3. 🚦 RATE LIMITING (Opcional)
|--------------------------------------------------------------------------
|
| Agregar throttle middleware a webhooks:
| ->middleware('throttle:60,1') // 60 requests por minuto
|
|--------------------------------------------------------------------------
| 4. 🌐 CORS (config/cors.php)
|--------------------------------------------------------------------------
|
| Si consumes la API desde frontend externo, configura orígenes permitidos
|
|--------------------------------------------------------------------------
| 5. 📚 DOCUMENTACIÓN API
|--------------------------------------------------------------------------
|
| Scramble OpenAPI: http://localhost:8001/docs/api
|
*/
