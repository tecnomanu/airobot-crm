<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type'); // appointment, sales, survey, support, qualification
            $table->text('description')->nullable();

            // Prompt sections (fixed and reusable)
            $table->longText('style_section'); // Conversation style, tone, regionalism
            $table->longText('behavior_section'); // Required behaviors
            $table->longText('data_section_template')->nullable(); // Template for data section

            // Available variables for this template
            $table->json('available_variables')->nullable(); // ["company", "product", "timezone"]

            // Retell configuration (base template)
            $table->json('retell_config_template')->nullable(); // voice, webhooks, tools, etc.

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_templates');
    }
};

