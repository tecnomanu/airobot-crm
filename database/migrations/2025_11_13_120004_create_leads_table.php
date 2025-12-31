<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone')->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable()
                ->comment('ISO2 country code');
            $table->string('option_selected')->nullable(); // 1, 2, i, t
            $table->uuid('campaign_id')->nullable();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');

            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->string('assignment_error')->nullable();

            // === STAGE: Single source of truth for UI ===
            $table->string('stage')->default('inbox')->index()
                ->comment('Pipeline stage: inbox, qualifying, sales_ready, closed');

            // Legacy status (kept for backwards compatibility, not source of truth)
            $table->string('status')->default('pending'); // pending, in_progress, contacted, closed, invalid
            $table->string('source')->default('webhook_inicial'); // webhook_inicial, whatsapp, agente_ia, manual
            $table->timestamp('sent_at')->nullable();

            // Intention tracking
            $table->text('intention')->nullable();
            $table->string('intention_origin')->nullable()
                ->comment('Source of intention: whatsapp, agent_ia, ivr, manual');
            $table->string('intention_status')->nullable()
                ->comment('Intention state: pending, finalized, sent_to_client');
            $table->timestamp('intention_decided_at')->nullable()
                ->comment('When intention was decided');
            $table->timestamp('exported_at')->nullable()
                ->comment('When lead was exported to client');

            // Notes and tags
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            // Manual classification (legacy)
            $table->string('manual_classification')->nullable();
            $table->text('decision_notes')->nullable();
            $table->boolean('ai_agent_active')->default(false);

            // === CLOSE FIELDS ===
            $table->timestamp('closed_at')->nullable();
            $table->string('close_reason')->nullable()
                ->comment('Final outcome: interested, not_interested, no_response, invalid_number, dnc, callback_requested, qualified, disqualified');
            $table->text('close_notes')->nullable();

            // Webhook tracking (legacy - to be replaced by lead_dispatch_attempts)
            $table->boolean('webhook_sent')->default(false);
            $table->text('webhook_result')->nullable();
            $table->boolean('intention_webhook_sent')->default(false);
            $table->timestamp('intention_webhook_sent_at')->nullable();
            $table->text('intention_webhook_response')->nullable();
            $table->string('intention_webhook_status')->nullable();

            // Automation fields
            $table->string('automation_status')->default('pending')
                ->comment('Automation engine state: pending, processing, completed, failed, skipped, paused');
            $table->timestamp('next_action_at')->nullable();
            $table->timestamp('last_automation_run_at')->nullable();
            $table->integer('automation_attempts')->default(0);
            $table->text('automation_error')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for common queries
            $table->index('status');
            $table->index('campaign_id');
            $table->index('client_id');
            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'stage']);
            $table->index(['automation_status', 'next_action_at']);
            $table->index(['phone', 'campaign_id']);
            $table->index('intention_status');
            $table->index('intention_decided_at');
            $table->index('closed_at');
            $table->index('close_reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
