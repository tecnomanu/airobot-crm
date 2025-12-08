<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_whatsapp_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->string('name');
            $table->uuid('source_id')->nullable();
            $table->foreign('source_id')->references('id')->on('sources')->nullOnDelete();
            $table->json('config')->nullable(); // Additional configuration
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_whatsapp_agents');
    }
};
