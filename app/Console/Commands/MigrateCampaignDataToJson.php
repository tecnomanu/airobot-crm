<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;

class MigrateCampaignDataToJson extends Command
{
    protected $signature = 'campaigns:migrate-to-json {--dry-run : Ejecutar sin modificar la base de datos}';
    protected $description = 'Migra datos de campaña de columnas individuales a estructura JSON';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Modo DRY RUN - No se modificará la base de datos');
        }

        $campaigns = Campaign::all();

        if ($campaigns->isEmpty()) {
            $this->warn('No hay campañas para migrar');
            return 0;
        }

        $this->info("📊 Encontradas {$campaigns->count()} campañas para migrar");
        $bar = $this->output->createProgressBar($campaigns->count());
        $bar->start();

        $migratedCount = 0;

        foreach ($campaigns as $campaign) {
            // Migrar agents_config
            $agentsConfig = $this->buildAgentsConfig($campaign);

            // Migrar options_config
            $optionsConfig = $this->buildOptionsConfig($campaign);

            // Migrar automation_config
            $automationConfig = $this->buildAutomationConfig($campaign);

            if (!$dryRun) {
                $campaign->update([
                    'agents_config' => $agentsConfig,
                    'options_config' => $optionsConfig,
                    'automation_config' => $automationConfig,
                ]);
            }

            $migratedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("✅ DRY RUN completado. {$migratedCount} campañas serían migradas");
            $this->info('Ejecuta sin --dry-run para aplicar los cambios');
        } else {
            $this->info("✅ Migración completada. {$migratedCount} campañas actualizadas");
        }

        return 0;
    }

    private function buildAgentsConfig(Campaign $campaign): array
    {
        return [
            'call' => [
                'enabled' => !empty($campaign->call_agent_name),
                'name' => $campaign->call_agent_name,
                'provider' => $campaign->call_agent_provider,
                'config' => $campaign->call_agent_config ?? [],
            ],
            'whatsapp' => [
                'enabled' => !empty($campaign->whatsapp_agent_name),
                'name' => $campaign->whatsapp_agent_name,
                'config' => $campaign->whatsapp_agent_config ?? [],
            ],
            'sources' => [
                'whatsapp_source_id' => $campaign->whatsapp_source_id,
                'webhook_source_id' => $campaign->webhook_source_id,
            ],
        ];
    }

    private function buildOptionsConfig(Campaign $campaign): array
    {
        $options = [];

        foreach (['1', '2', 'i', 't'] as $key) {
            $action = $campaign->{"option_{$key}_action"};

            // Solo incluir si tiene una acción definida
            if (!empty($action) && $action !== 'none') {
                $options[$key] = [
                    'action' => $action,
                    'source_id' => $campaign->{"option_{$key}_source_id"},
                    'template_id' => $campaign->{"option_{$key}_template_id"},
                    'message' => $campaign->{"option_{$key}_message"} ?? null,
                    'delay' => 5, // Default delay en minutos
                ];
            }
        }

        return $options;
    }

    private function buildAutomationConfig(Campaign $campaign): array
    {
        return [
            'enabled' => $campaign->auto_processing_enabled ?? false,
            'default_delay' => 5,
            'webhook' => [
                'enabled' => $campaign->webhook_enabled ?? false,
                'url' => $campaign->webhook_url,
                'method' => $campaign->webhook_method,
                'payload_template' => $campaign->webhook_payload_template,
            ],
        ];
    }
}
