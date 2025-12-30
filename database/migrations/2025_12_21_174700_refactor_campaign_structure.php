<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración refactoriza la estructura de campaigns para:
     * 1. Usar siempre campaign_options (directas usan option_key='0')
     * 2. Mover intention actions a tabla separada
     * 3. Limpiar fillable de campaigns
     */
    public function up(): void
    {
        // Crear tabla de acciones de intención
        Schema::create('campaign_intention_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');

            // Tipo de intención: 'interested' o 'not_interested'
            $table->string('intention_type'); // interested, not_interested

            // Tipo de acción: 'webhook', 'spreadsheet', 'none'
            $table->string('action_type')->default('none');

            // Webhook configuration
            $table->uuid('webhook_id')->nullable();
            $table->foreign('webhook_id')->references('id')->on('sources')->nullOnDelete();

            // Google Sheets configuration
            $table->uuid('google_integration_id')->nullable();
            $table->foreign('google_integration_id')->references('id')->on('google_integrations')->nullOnDelete();
            $table->string('google_spreadsheet_id')->nullable();
            $table->string('google_sheet_name')->nullable();

            // Estado
            $table->boolean('enabled')->default(false);

            $table->timestamps();

            // Unique constraint: una campaña solo puede tener una acción por tipo de intención
            $table->unique(['campaign_id', 'intention_type']);
            $table->index('campaign_id');
            $table->index('intention_type');
            $table->index('action_type');
        });

        // Migrar datos existentes de campaigns a campaign_intention_actions
        DB::table('campaigns')->orderBy('created_at')->chunk(100, function ($campaigns) {
            foreach ($campaigns as $campaign) {
                // Acción para interesados
                DB::table('campaign_intention_actions')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'campaign_id' => $campaign->id,
                    'intention_type' => 'interested',
                    'action_type' => $campaign->send_intention_interested_webhook ? 'webhook' : 'none',
                    'webhook_id' => $campaign->intention_interested_webhook_id,
                    'google_integration_id' => $campaign->google_integration_id,
                    'google_spreadsheet_id' => $campaign->google_spreadsheet_id,
                    'google_sheet_name' => $campaign->google_sheet_name,
                    'enabled' => (bool) $campaign->send_intention_interested_webhook || !empty($campaign->google_spreadsheet_id),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Acción para no interesados
                DB::table('campaign_intention_actions')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'campaign_id' => $campaign->id,
                    'intention_type' => 'not_interested',
                    'action_type' => $campaign->send_intention_not_interested_webhook ? 'webhook' : 'none',
                    'webhook_id' => $campaign->intention_not_interested_webhook_id,
                    'google_integration_id' => null,
                    'google_spreadsheet_id' => $campaign->intention_not_interested_google_spreadsheet_id ?? null,
                    'google_sheet_name' => $campaign->intention_not_interested_google_sheet_name ?? null,
                    'enabled' => (bool) $campaign->send_intention_not_interested_webhook || !empty($campaign->intention_not_interested_google_spreadsheet_id ?? ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // Migrar configuración de directas a campaign_options (option_key='0')
        DB::table('campaigns')->where('strategy_type', 'direct')->orderBy('created_at')->chunk(100, function ($campaigns) {
            foreach ($campaigns as $campaign) {
                $config = json_decode($campaign->configuration ?? '{}', true);

                // Solo crear opción si tiene configuración válida
                if (!empty($config['trigger_action'])) {
                    DB::table('campaign_options')->insert([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'campaign_id' => $campaign->id,
                        'option_key' => '0', // Campañas directas usan key '0'
                        'action' => $config['trigger_action'] ?? 'skip',
                        'source_id' => $config['source_id'] ?? null,
                        'template_id' => $config['template_id'] ?? null,
                        'message' => $config['message'] ?? null,
                        'delay' => $config['delay_seconds'] ?? 5,
                        'enabled' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        // Eliminar columnas antiguas de campaigns
        Schema::table('campaigns', function (Blueprint $table) {
            // Eliminar índices relacionados PRIMERO (SQLite requirement)
            $table->dropIndex(['send_intention_interested_webhook']);
            $table->dropIndex(['send_intention_not_interested_webhook']);

            // Luego eliminar foreign keys
            $table->dropForeign(['intention_interested_webhook_id']);
            $table->dropForeign(['intention_not_interested_webhook_id']);
            $table->dropForeign(['google_integration_id']);

            // Finalmente eliminar columnas
            $table->dropColumn([
                'intention_interested_webhook_id',
                'intention_not_interested_webhook_id',
                'send_intention_interested_webhook',
                'send_intention_not_interested_webhook',
                'google_integration_id',
                'google_spreadsheet_id',
                'google_sheet_name',
                'configuration',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar columnas en campaigns
        Schema::table('campaigns', function (Blueprint $table) {
            $table->uuid('intention_interested_webhook_id')->nullable();
            $table->uuid('intention_not_interested_webhook_id')->nullable();
            $table->boolean('send_intention_interested_webhook')->default(false);
            $table->boolean('send_intention_not_interested_webhook')->default(false);
            $table->foreignUuid('google_integration_id')->nullable()->constrained('google_integrations')->nullOnDelete();
            $table->string('google_spreadsheet_id')->nullable();
            $table->string('google_sheet_name')->nullable();
            $table->string('intention_not_interested_google_spreadsheet_id')->nullable();
            $table->string('intention_not_interested_google_sheet_name')->nullable();

            $table->index('send_intention_interested_webhook');
            $table->index('send_intention_not_interested_webhook');
        });

        // Migrar datos de vuelta
        DB::table('campaign_intention_actions')->orderBy('created_at')->chunk(100, function ($actions) {
            foreach ($actions as $action) {
                if ($action->intention_type === 'interested') {
                    DB::table('campaigns')->where('id', $action->campaign_id)->update([
                        'intention_interested_webhook_id' => $action->webhook_id,
                        'send_intention_interested_webhook' => $action->action_type === 'webhook' && $action->enabled,
                        'google_integration_id' => $action->google_integration_id,
                        'google_spreadsheet_id' => $action->google_spreadsheet_id,
                        'google_sheet_name' => $action->google_sheet_name,
                    ]);
                } else {
                    DB::table('campaigns')->where('id', $action->campaign_id)->update([
                        'intention_not_interested_webhook_id' => $action->webhook_id,
                        'send_intention_not_interested_webhook' => $action->action_type === 'webhook' && $action->enabled,
                        'intention_not_interested_google_spreadsheet_id' => $action->google_spreadsheet_id,
                        'intention_not_interested_google_sheet_name' => $action->google_sheet_name,
                    ]);
                }
            }
        });

        // Eliminar options de campañas directas (option_key='0')
        DB::table('campaign_options')->where('option_key', '0')->delete();

        // Eliminar tabla de intention actions
        Schema::dropIfExists('campaign_intention_actions');
    }
};
