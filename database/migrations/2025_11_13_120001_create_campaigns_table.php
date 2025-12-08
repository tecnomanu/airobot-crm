<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, paused
            $table->string('campaign_type')->default('inbound')
                ->comment('Campaign type: inbound (reactive, IVR), outbound (proactive, bulk)');
            $table->string('export_rule')->default('interested_only')
                ->comment('Regla de exportación: interested_only, not_interested_only, both, none');
            $table->string('match_pattern')->nullable()->unique();
            $table->string('campaign_slug')->nullable()->unique();
            $table->boolean('auto_process_enabled')->default(true);
            $table->string('country', 2)->default('AR')
                ->comment('Código ISO2 del país objetivo de la campaña');

            // Webhooks for intention-based sending
            $table->uuid('intention_interested_webhook_id')->nullable();
            $table->uuid('intention_not_interested_webhook_id')->nullable();
            $table->boolean('send_intention_interested_webhook')->default(false)
                ->comment('Enviar webhook cuando se detecta intención de interesado');
            $table->boolean('send_intention_not_interested_webhook')->default(false)
                ->comment('Enviar webhook cuando se detecta intención de no interesado');

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('client_id');
            $table->index('campaign_type');
            $table->index('campaign_slug');
            $table->index('send_intention_interested_webhook');
            $table->index('send_intention_not_interested_webhook');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
