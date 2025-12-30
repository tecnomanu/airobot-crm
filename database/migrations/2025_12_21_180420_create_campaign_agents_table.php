<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaign_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');

            $table->uuid('agent_template_id');
            $table->foreign('agent_template_id')->references('id')->on('agent_templates')->onDelete('restrict');

            $table->string('name');

            // Prompt de intención (input del usuario)
            $table->text('intention_prompt'); // "Agendar citas para Toyota plan de ahorro"

            // Variables específicas de esta campaña
            $table->json('variables')->nullable(); // {"company": "Toyota", "product": "Plan de Ahorro"}

            // Sección de flujo generada por LLM
            $table->longText('flow_section')->nullable();

            // Prompt final compuesto (cache)
            $table->longText('final_prompt')->nullable();

            // Retell sync
            $table->string('retell_agent_id')->nullable(); // ID del agente en Retell
            $table->json('retell_config')->nullable(); // Configuración completa de Retell
            $table->boolean('is_synced')->default(false);
            $table->timestamp('last_synced_at')->nullable();

            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('agent_template_id');
            $table->index('retell_agent_id');
            $table->index('is_synced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_agents');
    }
};
