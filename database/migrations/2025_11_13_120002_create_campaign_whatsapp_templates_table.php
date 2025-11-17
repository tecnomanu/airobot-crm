<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_whatsapp_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->string('code')->index(); // ej: "option_2_initial", "option_i_followup"
            $table->string('name');
            $table->text('body');
            $table->json('attachments')->nullable(); // URLs o paths de archivos/imágenes
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Índice compuesto para buscar templates por campaña y código
            $table->unique(['campaign_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_whatsapp_templates');
    }
};
