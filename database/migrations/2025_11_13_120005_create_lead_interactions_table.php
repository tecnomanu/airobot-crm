<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id')->nullable();
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->uuid('campaign_id')->nullable();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            $table->string('channel')->index(); // whatsapp, call, email, etc.
            $table->string('direction')->index(); // inbound, outbound
            $table->text('content'); // Cuerpo principal del mensaje
            $table->json('payload')->nullable(); // Payload crudo del proveedor
            $table->string('external_id')->nullable()->unique(); // ID externo del mensaje (para evitar duplicados)
            $table->string('phone')->nullable()->index(); // Teléfono (útil antes de matchear con lead)
            $table->timestamps();

            // Índices para búsquedas comunes
            $table->index(['lead_id', 'created_at']);
            $table->index(['campaign_id', 'created_at']);
            $table->index(['channel', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_interactions');
    }
};

