<?php

use App\Http\Controllers\Web\LeadCallController;
use App\Http\Controllers\Web\Campaign\CampaignController;
use App\Http\Controllers\Web\CalculatorController;
use App\Http\Controllers\Web\Client\ClientController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\Lead\LeadController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\SourceController;
use App\Http\Controllers\Web\WebhookConfigController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings
    Route::inertia('/settings/integrations', 'Settings/Integrations')->name('settings.integrations');

    // Leads - Note: Using closure for index due to route resolution issue
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/', function (\Illuminate\Http\Request $request) {
            return app(LeadController::class)->index($request);
        })->name('index');
        Route::get('/{id}', function (\Illuminate\Http\Request $request, string $id) {
            return app(LeadController::class)->show($id);
        })->name('show');
        Route::post('/import-csv', [LeadController::class, 'importCSV'])->name('import-csv');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::put('/{id}', [LeadController::class, 'update'])->name('update');
        Route::delete('/{id}', [LeadController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/retry-automation', [LeadController::class, 'retryAutomation'])->name('retry-automation');
        Route::post('/retry-automation-batch', [LeadController::class, 'retryAutomationBatch'])->name('retry-automation-batch');
        Route::post('/{id}/call', [LeadController::class, 'initiateCall'])->name('call-action');
        Route::post('/{id}/whatsapp', [LeadController::class, 'initiateWhatsapp'])->name('whatsapp-action');
    });

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/create', [CampaignController::class, 'create'])->name('create');
        Route::get('/{id}', [CampaignController::class, 'show'])->name('show');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::put('/{id}', [CampaignController::class, 'update'])->name('update');
        Route::delete('/{id}', [CampaignController::class, 'destroy'])->name('destroy');
        Route::patch('/{campaign}/toggle-status', [CampaignController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Clients
    Route::resource('clients', ClientController::class);

    // Lead Calls (Call History)
    Route::prefix('lead-calls')->name('lead-calls.')->group(function () {
        Route::get('/', [LeadCallController::class, 'index'])->name('index');
        Route::get('/{id}', [LeadCallController::class, 'show'])->name('show');
    });

    // Messages (Chat Interface)
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', function (\Illuminate\Http\Request $request) {
            return app(\App\Http\Controllers\Web\Message\MessagesController::class)->index($request);
        })->name('index');
        Route::get('/{leadId}/messages', [\App\Http\Controllers\Web\Message\MessagesController::class, 'getMessages'])->name('get');
        Route::post('/{leadId}/send', [\App\Http\Controllers\Web\Message\MessagesController::class, 'sendMessage'])->name('send');
        Route::post('/{leadId}/toggle-ai', [\App\Http\Controllers\Web\Message\MessagesController::class, 'toggleAiAgent'])->name('toggle-ai');
    });

    // Webhook Configuration
    Route::get('/webhook-config', [WebhookConfigController::class, 'index'])->name('webhook-config.index');

    // Sources (Fuentes)
    Route::resource('sources', SourceController::class)->except(['show']);
    Route::get('/api/sources/active/{type}', [\App\Http\Controllers\Api\Source\SourceController::class, 'getActiveByType'])->name('sources.active-by-type');

    // Call Agents (Agentes de Retell)
    Route::prefix('call-agents')->name('call-agents.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\CallAgent\CallAgentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Web\CallAgent\CallAgentController::class, 'create'])->name('create');
        Route::get('/{id}', [\App\Http\Controllers\Web\CallAgent\CallAgentController::class, 'show'])->name('show');
        Route::post('/', [\App\Http\Controllers\Web\CallAgent\CallAgentController::class, 'store'])->name('store');
        Route::put('/{id}', [\App\Http\Controllers\Web\CallAgent\CallAgentController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Web\CallAgent\CallAgentController::class, 'destroy'])->name('destroy');
    });

    // AI Agent Templates
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('agent-templates', \App\Http\Controllers\Web\AI\AgentTemplateController::class);
        Route::post('agent-templates/{agentTemplate}/duplicate', [\App\Http\Controllers\Web\AI\AgentTemplateController::class, 'duplicate'])
            ->name('agent-templates.duplicate');
    });

    // Campaign Agents (AI)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('campaign-agents', \App\Http\Controllers\Web\AI\CampaignAgentController::class);
        Route::post('campaign-agents/{campaignAgent}/generate', [\App\Http\Controllers\Web\AI\CampaignAgentController::class, 'generate'])
            ->name('campaign-agents.generate');
        Route::post('campaign-agents/{campaignAgent}/sync', [\App\Http\Controllers\Web\AI\CampaignAgentController::class, 'sync'])
            ->name('campaign-agents.sync');
        Route::post('campaign-agents/preview', [\App\Http\Controllers\Web\AI\CampaignAgentController::class, 'preview'])
            ->name('campaign-agents.preview');
    });


    // Calculator
    Route::prefix('calculator')->name('calculator.')->group(function () {
        Route::get('/', [CalculatorController::class, 'index'])->name('index');
        Route::post('/create', [CalculatorController::class, 'create'])->name('create');
        Route::get('/{id}', [CalculatorController::class, 'show'])->name('show');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

require __DIR__ . '/auth.php';
