<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lead Dispatch Attempts - Audit/log table for all external dispatches
     *
     * Tracks webhook and Google Sheet exports with:
     * - Full request/response logging
     * - Retry support with attempt counting
     * - Idempotency (prevent duplicate successful sends)
     */
    public function up(): void
    {
        Schema::create('lead_dispatch_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');

            // Traceability
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->uuid('campaign_id')->nullable();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->nullOnDelete();

            // Dispatch type and trigger
            $table->string('type'); // webhook, google_sheet
            $table->string('trigger'); // on_interested, on_not_interested, on_no_response, manual

            // Destination reference
            $table->uuid('destination_id')->nullable()
                ->comment('Reference to source/webhook config or intention action ID');

            // Request data
            $table->json('request_payload')->nullable();
            $table->string('request_url')->nullable();
            $table->string('request_method')->nullable()->default('POST');

            // Response data
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();

            // Status tracking
            $table->string('status')->default('pending'); // pending, success, failed, retrying
            $table->unsignedTinyInteger('attempt_no')->default(1);
            $table->timestamp('next_retry_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index('lead_id');
            $table->index(['lead_id', 'type', 'trigger']);
            $table->index(['status', 'next_retry_at']);
            $table->index('client_id');
            $table->index('campaign_id');
            $table->index('type');
            $table->index('trigger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_dispatch_attempts');
    }
};
