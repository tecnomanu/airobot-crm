<?php

use App\Http\Controllers\Api\CalculatorController;
use App\Http\Controllers\Api\Campaign\CampaignAssigneeController;
use App\Http\Controllers\Api\Campaign\CampaignController;
use App\Http\Controllers\Api\Client\ClientController;
use App\Http\Controllers\Api\Client\ClientDispatchController;
use App\Http\Controllers\Api\Lead\LeadAssignmentController;
use App\Http\Controllers\Api\Lead\LeadCallController;
use App\Http\Controllers\Api\Lead\LeadController;
use App\Http\Controllers\Api\Reporting\ReportingController;
use App\Http\Controllers\Api\Source\SourceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel API Routes (Internal JSON endpoints for admin panel)
|--------------------------------------------------------------------------
|
| These routes serve JSON responses for the admin panel's AJAX/fetch calls.
| They use web session authentication (not Sanctum tokens).
|
| Prefix: /panel-api
| Middleware: web, auth (session-based)
| Response format: JSON
|
*/

Route::middleware(['web', 'auth'])->prefix('panel-api')->name('panel.')->group(function () {

    // ─────────────────────────────────────────────────────────────────────
    // 👥 LEADS - JSON API for admin panel
    // ─────────────────────────────────────────────────────────────────────

    Route::apiResource('leads', LeadController::class);

    // Lead activities (timeline with calls, messages, etc.)
    Route::get('leads/{lead}/activities', [LeadController::class, 'activities'])
        ->name('leads.activities');

    // @deprecated - Use leads/{lead}/activities instead
    Route::get('leads/{lead}/interactions', [LeadController::class, 'interactions'])
        ->name('leads.interactions');

    // Lead assignment
    Route::post('leads/{lead}/assign', [LeadAssignmentController::class, 'assign'])
        ->name('leads.assign');
    Route::delete('leads/{lead}/assign', [LeadAssignmentController::class, 'unassign'])
        ->name('leads.unassign');
    Route::post('leads/{lead}/retry-assignment', [LeadAssignmentController::class, 'retryAssignment'])
        ->name('leads.retry-assignment');

    // ─────────────────────────────────────────────────────────────────────
    // 📢 CAMPAIGNS - JSON API for admin panel
    // ─────────────────────────────────────────────────────────────────────

    Route::apiResource('campaigns', CampaignController::class);

    // WhatsApp templates per campaign
    Route::prefix('campaigns/{campaignId}')->name('campaigns.')->group(function () {
        Route::get('templates', [CampaignController::class, 'getTemplates'])
            ->name('templates.index');
        Route::post('templates', [CampaignController::class, 'storeTemplate'])
            ->name('templates.store');
        Route::put('templates/{templateId}', [CampaignController::class, 'updateTemplate'])
            ->name('templates.update');
        Route::delete('templates/{templateId}', [CampaignController::class, 'destroyTemplate'])
            ->name('templates.destroy');

        // Campaign assignees (sales reps)
        Route::get('assignees', [CampaignAssigneeController::class, 'index'])
            ->name('assignees.index');
        Route::post('assignees/sync', [CampaignAssigneeController::class, 'sync'])
            ->name('assignees.sync');
    });

    // Available users for assignment
    Route::get('users/available-assignees', [CampaignAssigneeController::class, 'availableUsers'])
        ->name('users.available-assignees');

    // ─────────────────────────────────────────────────────────────────────
    // 🏢 CLIENTS - JSON API for admin panel
    // ─────────────────────────────────────────────────────────────────────

    Route::apiResource('clients', ClientController::class);

    // Lead dispatch to client
    Route::prefix('clients/{client}')->name('clients.')->group(function () {
        Route::post('leads/{lead}/dispatch', [ClientDispatchController::class, 'dispatch'])
            ->name('leads.dispatch');
        Route::get('leads/{lead}/dispatch-status', [ClientDispatchController::class, 'status'])
            ->name('leads.dispatch.status');
    });

    // ─────────────────────────────────────────────────────────────────────
    // 📞 LEAD CALLS - Read-only
    // ─────────────────────────────────────────────────────────────────────

    Route::prefix('lead-calls')->name('lead-calls.')->group(function () {
        Route::get('/', [LeadCallController::class, 'index'])->name('index');
        Route::get('/{id}', [LeadCallController::class, 'show'])->name('show');
    });

    // ─────────────────────────────────────────────────────────────────────
    // 📡 SOURCES - Active sources by type
    // ─────────────────────────────────────────────────────────────────────

    Route::get('sources/active/{type}', [SourceController::class, 'getActiveByType'])
        ->name('sources.active-by-type');

    // ─────────────────────────────────────────────────────────────────────
    // 📊 REPORTING & METRICS
    // ─────────────────────────────────────────────────────────────────────

    Route::prefix('reporting')->name('reporting.')->group(function () {
        // Global dashboard metrics
        Route::get('metrics', [ReportingController::class, 'globalMetrics'])
            ->name('metrics');

        // Campaign performance
        Route::get('campaigns/performance', [ReportingController::class, 'campaignPerformance'])
            ->name('campaigns.performance');

        // Client reports
        Route::get('clients/{client}/overview', [ReportingController::class, 'clientOverview'])
            ->name('clients.overview');
        Route::get('clients/{client}/monthly-summary', [ReportingController::class, 'clientMonthlySummary'])
            ->name('clients.monthly');
    });

    // ─────────────────────────────────────────────────────────────────────
    // 🧮 CALCULATOR - Spreadsheet management
    // ─────────────────────────────────────────────────────────────────────

    Route::prefix('calculator')->name('calculator.')->group(function () {
        Route::get('/', [CalculatorController::class, 'index'])->name('index');
        Route::post('/', [CalculatorController::class, 'store'])->name('store');
        Route::get('/{id}', [CalculatorController::class, 'show'])->name('show');
        Route::put('/{id}/name', [CalculatorController::class, 'updateName'])->name('update-name');
        Route::put('/{id}/state', [CalculatorController::class, 'saveState'])->name('save-state');
        Route::delete('/{id}', [CalculatorController::class, 'destroy'])->name('destroy');

        // Granular endpoints with event sourcing
        Route::post('/{id}/cells', [CalculatorController::class, 'updateCells'])->name('update-cells');
        Route::put('/{id}/columns/{column}/width', [CalculatorController::class, 'updateColumnWidth'])->name('update-column-width');
        Route::put('/{id}/rows/{row}/height', [CalculatorController::class, 'updateRowHeight'])->name('update-row-height');
        Route::post('/{id}/cursor', [CalculatorController::class, 'moveCursor'])->name('move-cursor');
    });
});

