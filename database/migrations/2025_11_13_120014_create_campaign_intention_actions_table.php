<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campaign Intention Actions - Post-intention dispatch configuration
     *
     * Defines what happens when a lead's intention is determined:
     * - Send to webhook
     * - Export to Google Sheets
     * - Both or none
     */
    public function up(): void
    {
        Schema::create('campaign_intention_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');

            // Intention type: 'interested', 'not_interested', 'no_response'
            $table->string('intention_type');

            // Action type: 'webhook', 'spreadsheet', 'none'
            $table->string('action_type')->default('none');

            // Webhook configuration
            $table->uuid('webhook_id')->nullable();
            $table->foreign('webhook_id')->references('id')->on('sources')->nullOnDelete();

            // Google Sheets configuration
            $table->uuid('google_integration_id')->nullable();
            $table->foreign('google_integration_id')->references('id')->on('google_integrations')->nullOnDelete();
            $table->string('google_spreadsheet_id')->nullable();
            $table->string('google_sheet_name')->nullable();

            // State
            $table->boolean('enabled')->default(false);

            $table->timestamps();

            // Unique constraint: one action per intention type per campaign
            $table->unique(['campaign_id', 'intention_type']);
            $table->index('campaign_id');
            $table->index('intention_type');
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_intention_actions');
    }
};

