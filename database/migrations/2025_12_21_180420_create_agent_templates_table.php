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
        Schema::create('agent_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type'); // appointment, sales, survey, support, qualification
            $table->text('description')->nullable();

            // Secciones del prompt (fijas y reutilizables)
            $table->longText('style_section'); // Estilo de conversación, tono, regionalismo
            $table->longText('behavior_section'); // Comportamientos obligatorios
            $table->longText('data_section_template')->nullable(); // Template para sección de datos

            // Variables disponibles para este template
            $table->json('available_variables')->nullable(); // ["company", "product", "timezone"]

            // Configuración de Retell (template base)
            $table->json('retell_config_template')->nullable(); // voice, webhooks base, tools, etc.

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_templates');
    }
};
