<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // Intention prompt (user input)
            $table->text('intention_prompt'); // "Schedule appointments for Toyota savings plan"

            // Campaign-specific variables
            $table->json('variables')->nullable(); // {"company": "Toyota", "product": "Savings Plan"}

            // LLM-generated flow section
            $table->longText('flow_section')->nullable();

            // Final composed prompt (cache)
            $table->longText('final_prompt')->nullable();

            // Retell sync
            $table->string('retell_agent_id')->nullable(); // Retell agent ID
            $table->json('retell_config')->nullable(); // Complete Retell configuration
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

    public function down(): void
    {
        Schema::dropIfExists('campaign_agents');
    }
};

