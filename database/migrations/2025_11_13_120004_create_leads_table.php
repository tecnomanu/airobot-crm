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
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable()
                ->comment('Código ISO2 del país del lead');
            $table->string('option_selected')->nullable(); // 1, 2, i, t
            $table->uuid('campaign_id')->nullable();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, in_progress, contacted, closed, invalid
            $table->string('source')->default('webhook_inicial'); // webhook_inicial, whatsapp, agente_ia, manual
            $table->timestamp('sent_at')->nullable();
            $table->text('intention')->nullable();
            $table->string('intention_origin')->nullable()
                ->comment('Origen de la intención: whatsapp, agent_ia, ivr, manual');
            $table->string('intention_status')->nullable()
                ->comment('Estado de la intención: pending, finalized, sent_to_client');
            $table->timestamp('intention_decided_at')->nullable()
                ->comment('Fecha en que se decidió la intención');
            $table->timestamp('exported_at')->nullable()
                ->comment('Fecha en que se exportó el lead al cliente');
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            // Webhook tracking
            $table->boolean('webhook_sent')->default(false);
            $table->text('webhook_result')->nullable();
            $table->boolean('intention_webhook_sent')->default(false);
            $table->timestamp('intention_webhook_sent_at')->nullable();
            $table->text('intention_webhook_response')->nullable()
                ->comment('Respuesta del webhook de intención');
            $table->string('intention_webhook_status')->nullable()
                ->comment('Estado del envío: success, failed');

            // Automation fields
            $table->string('automation_status')->default('pending')
                ->comment('pending, processing, completed, failed, skipped');
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
            $table->index(['automation_status', 'next_action_at']);
            $table->index(['phone', 'campaign_id']);
            $table->index('intention_status');
            $table->index('intention_decided_at');
            $table->index('intention_webhook_sent');
            $table->index('intention_webhook_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

