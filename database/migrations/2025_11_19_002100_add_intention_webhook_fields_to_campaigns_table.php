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
            // Webhooks para envío según intención detectada
            $table->uuid('intention_interested_webhook_id')->nullable()->after('export_rule');
            $table->foreign('intention_interested_webhook_id')
                ->references('id')->on('sources')
                ->onDelete('set null');

            $table->uuid('intention_not_interested_webhook_id')->nullable()->after('intention_interested_webhook_id');
            $table->foreign('intention_not_interested_webhook_id')
                ->references('id')->on('sources')
                ->onDelete('set null');

            // Switches para activar/desactivar envío
            $table->boolean('send_intention_interested_webhook')->default(false)
                ->after('intention_not_interested_webhook_id')
                ->comment('Enviar webhook cuando se detecta intención de interesado');

            $table->boolean('send_intention_not_interested_webhook')->default(false)
                ->after('send_intention_interested_webhook')
                ->comment('Enviar webhook cuando se detecta intención de no interesado');

            // Índices para consultas
            $table->index('send_intention_interested_webhook');
            $table->index('send_intention_not_interested_webhook');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['intention_interested_webhook_id']);
            $table->dropForeign(['intention_not_interested_webhook_id']);
            $table->dropIndex(['send_intention_interested_webhook']);
            $table->dropIndex(['send_intention_not_interested_webhook']);
            $table->dropColumn([
                'intention_interested_webhook_id',
                'intention_not_interested_webhook_id',
                'send_intention_interested_webhook',
                'send_intention_not_interested_webhook',
            ]);
        });
    }
};
