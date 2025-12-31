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
        Schema::table('clients', function (Blueprint $table) {
            // Default agents for the client (can be inherited by campaigns)
            $table->foreignUuid('default_call_agent_id')
                ->nullable()
                ->after('notes')
                ->constrained('retell_agents')
                ->nullOnDelete();

            $table->foreignUuid('default_whatsapp_source_id')
                ->nullable()
                ->after('default_call_agent_id')
                ->constrained('sources')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_call_agent_id');
            $table->dropConstrainedForeignId('default_whatsapp_source_id');
        });
    }
};
