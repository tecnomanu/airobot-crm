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
            // Tracking de envío de webhook de intención
            $table->boolean('intention_webhook_sent')->default(false)->after('webhook_result');
            $table->timestamp('intention_webhook_sent_at')->nullable()->after('intention_webhook_sent');
            $table->text('intention_webhook_response')->nullable()->after('intention_webhook_sent_at')
                ->comment('Respuesta del webhook de intención');
            $table->string('intention_webhook_status')->nullable()->after('intention_webhook_response')
                ->comment('Estado del envío: success, failed');

            // Índice para consultas
            $table->index('intention_webhook_sent');
            $table->index('intention_webhook_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['intention_webhook_sent']);
            $table->dropIndex(['intention_webhook_status']);
            $table->dropColumn([
                'intention_webhook_sent',
                'intention_webhook_sent_at',
                'intention_webhook_response',
                'intention_webhook_status',
            ]);
        });
    }
};
