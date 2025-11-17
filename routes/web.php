<?php

use App\Http\Controllers\Web\CallHistoryController;
use App\Http\Controllers\Web\Campaign\CampaignController;
use App\Http\Controllers\Web\Client\ClientController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\Lead\LeadController;
use App\Http\Controllers\Web\Lead\LeadIntencionController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\SourceController;
use App\Http\Controllers\Web\WebhookConfigController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Leads
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::get('/{id}', [LeadController::class, 'show'])->name('show');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::put('/{id}', [LeadController::class, 'update'])->name('update');
        Route::delete('/{id}', [LeadController::class, 'destroy'])->name('destroy');
    });

    // Leads Intención
    Route::get('/leads-intencion', [LeadIntencionController::class, 'index'])->name('leads-intencion.index');

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/{id}', [CampaignController::class, 'show'])->name('show');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::put('/{id}', [CampaignController::class, 'update'])->name('update');
        Route::delete('/{id}', [CampaignController::class, 'destroy'])->name('destroy');
    });

    // Clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/{id}', [ClientController::class, 'show'])->name('show');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        Route::put('/{id}', [ClientController::class, 'update'])->name('update');
        Route::delete('/{id}', [ClientController::class, 'destroy'])->name('destroy');
    });

    // Call History
    Route::prefix('call-history')->name('call-history.')->group(function () {
        Route::get('/', [CallHistoryController::class, 'index'])->name('index');
        Route::get('/{id}', [CallHistoryController::class, 'show'])->name('show');
    });

    // Webhook Configuration
    Route::get('/webhook-config', [WebhookConfigController::class, 'index'])->name('webhook-config.index');

    // Sources (Fuentes)
    Route::resource('sources', SourceController::class)->except(['show']);
    Route::get('/api/sources/active/{type}', [\App\Http\Controllers\Api\SourceController::class, 'getActiveByType'])->name('sources.active-by-type');
});

require __DIR__.'/auth.php';
