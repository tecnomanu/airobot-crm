<?php

use App\Http\Controllers\Web\LeadCallController;
use App\Http\Controllers\Web\Campaign\CampaignController;
use App\Http\Controllers\Web\CalculatorController;
use App\Http\Controllers\Web\Client\ClientController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\Lead\LeadController;
use App\Http\Controllers\Web\Lead\LeadIntencionController;
use App\Http\Controllers\Web\Lead\LeadsManagerController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\SourceController;
use App\Http\Controllers\Web\WebhookConfigController;
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

    // Leads Manager (Unified View with Tabs)
    Route::prefix('leads')->name('leads-manager.')->group(function () {
        Route::get('/', [LeadsManagerController::class, 'index'])->name('index');
        Route::get('/{id}', [LeadsManagerController::class, 'show'])->name('show');
        Route::post('/', [LeadsManagerController::class, 'store'])->name('store');
        Route::put('/{id}', [LeadsManagerController::class, 'update'])->name('update');
        Route::delete('/{id}', [LeadsManagerController::class, 'destroy'])->name('destroy');

        // Automation retry
        Route::post('/{id}/retry-automation', [LeadsManagerController::class, 'retryAutomation'])->name('retry-automation');
        Route::post('/retry-automation-batch', [LeadsManagerController::class, 'retryAutomationBatch'])->name('retry-automation-batch');

        // Quick actions
        Route::post('/{id}/call', [LeadsManagerController::class, 'callAction'])->name('call-action');
        Route::post('/{id}/whatsapp', [LeadsManagerController::class, 'whatsappAction'])->name('whatsapp-action');
    });

    // Legacy routes removed - use leads-manager instead

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/{id}', [CampaignController::class, 'show'])->name('show');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::put('/{id}', [CampaignController::class, 'update'])->name('update');
        Route::delete('/{id}', [CampaignController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-status', [CampaignController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/{id}', [ClientController::class, 'show'])->name('show');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        Route::put('/{id}', [ClientController::class, 'update'])->name('update');
        Route::delete('/{id}', [ClientController::class, 'destroy'])->name('destroy');
    });

    // Lead Calls (formerly Call History)
    Route::prefix('lead-calls')->name('lead-calls.')->group(function () {
        Route::get('/', [LeadCallController::class, 'index'])->name('index');
        Route::get('/{id}', [LeadCallController::class, 'show'])->name('show');
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

    // Calculator
    Route::prefix('calculator')->name('calculator.')->group(function () {
        Route::get('/', [CalculatorController::class, 'index'])->name('index');
        Route::post('/create', [CalculatorController::class, 'create'])->name('create');
        Route::get('/{id}', [CalculatorController::class, 'show'])->name('show');
    });
});

require __DIR__ . '/auth.php';
