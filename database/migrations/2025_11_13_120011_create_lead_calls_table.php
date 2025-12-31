<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lead Calls - Detailed call records with metrics
     *
     * Stores all call-specific data with strict columns for:
     * - Duration metrics
     * - Cost/billing calculations
     * - Recording URLs
     * - Provider integration data
     *
     * Enables direct SQL queries like: LeadCall::sum('cost')
     */
    public function up(): void
    {
        Schema::create('lead_calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->uuid('campaign_id')->nullable();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            $table->string('phone')->index();

            // Call metrics (strict columns for SQL aggregations)
            $table->integer('duration_seconds')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->timestamp('call_date')->index();

            // Status and outcome
            $table->string('status'); // completed, no_answer, hung_up, failed, busy, voicemail

            // Provider integration
            $table->string('provider')->nullable(); // retell, vapi, etc.
            $table->string('retell_call_id')->nullable()->index();

            // Recording and transcript
            $table->string('recording_url')->nullable();
            $table->longText('transcript')->nullable();

            // Additional metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Provider-specific raw data

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for common queries
            $table->index('lead_id');
            $table->index('campaign_id');
            $table->index('status');
            $table->index(['lead_id', 'created_at']);
            $table->index(['campaign_id', 'call_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_calls');
    }
};
