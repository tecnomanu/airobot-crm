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
            $table->string('option_selected')->nullable(); // 1, 2, i, t
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, in_progress, contacted, closed, invalid
            $table->string('source')->default('webhook_inicial'); // webhook_inicial, whatsapp, agente_ia, manual
            $table->timestamp('sent_at')->nullable();
            $table->text('intention')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            
            // Webhook tracking
            $table->boolean('webhook_sent')->default(false);
            $table->text('webhook_result')->nullable();

            // Automation fields
            $table->string('automation_status')->default('pending')
                  ->comment('pending, processing, completed, failed, skipped');
            $table->timestamp('next_action_at')->nullable();
            $table->timestamp('last_automation_run_at')->nullable();
            $table->integer('automation_attempts')->default(0);
            $table->text('automation_error')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índices para búsquedas comunes
            $table->index('status');
            $table->index('campaign_id');
            $table->index(['automation_status', 'next_action_at']);
            $table->index(['phone', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

