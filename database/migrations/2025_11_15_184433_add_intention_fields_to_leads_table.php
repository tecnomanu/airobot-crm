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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('intention_origin')->nullable()->after('intention')
                ->comment('Origen de la intención: whatsapp, agent_ia, ivr, manual');
            $table->string('intention_status')->nullable()->after('intention_origin')
                ->comment('Estado de la intención: pending, finalized, sent_to_client');
            $table->timestamp('intention_decided_at')->nullable()->after('intention_status')
                ->comment('Fecha en que se decidió la intención');
            $table->timestamp('exported_at')->nullable()->after('intention_decided_at')
                ->comment('Fecha en que se exportó el lead al cliente');

            // Índices para consultas eficientes
            $table->index('intention_status');
            $table->index('intention_decided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['intention_status']);
            $table->dropIndex(['intention_decided_at']);
            $table->dropColumn([
                'intention_origin',
                'intention_status',
                'intention_decided_at',
                'exported_at',
            ]);
        });
    }
};
