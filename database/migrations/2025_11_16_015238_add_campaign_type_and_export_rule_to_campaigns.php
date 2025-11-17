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
            $table->string('campaign_type')->default('filtering')->after('status')
                ->comment('Tipo de campaña: filtering (IVR), direct_call (llamado directo)');
            $table->string('export_rule')->default('interested_only')->after('campaign_type')
                ->comment('Regla de exportación: interested_only, not_interested_only, both, none');

            // Índice para consultas
            $table->index('campaign_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['campaign_type']);
            $table->dropColumn(['campaign_type', 'export_rule']);
        });
    }
};
