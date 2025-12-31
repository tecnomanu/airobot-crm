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
        Schema::table('campaigns', function (Blueprint $table) {
            // Flags to inherit defaults from client
            $table->boolean('use_client_call_defaults')
                ->default(true)
                ->after('auto_process_enabled')
                ->comment('If true, use client default_call_agent when campaign has none');

            $table->boolean('use_client_whatsapp_defaults')
                ->default(true)
                ->after('use_client_call_defaults')
                ->comment('If true, use client default_whatsapp_source when campaign has none');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['use_client_call_defaults', 'use_client_whatsapp_defaults']);
        });
    }
};
