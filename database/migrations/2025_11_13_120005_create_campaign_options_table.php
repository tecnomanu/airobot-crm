<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->string('option_key'); // 1, 2, i, t
            $table->string('action'); // webhook, whatsapp, none, etc.
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->uuid('template_id')->nullable();
            $table->foreign('template_id')->references('id')->on('campaign_whatsapp_templates')->nullOnDelete();
            $table->text('message')->nullable(); // Mensaje personalizado si no usa template
            $table->integer('delay')->default(5); // Delay en segundos antes de ejecutar
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['campaign_id', 'option_key']);
            $table->index('campaign_id');
            $table->index('source_id');
            $table->index('template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_options');
    }
};
