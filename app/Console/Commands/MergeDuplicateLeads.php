<?php

namespace App\Console\Commands;

use App\Models\Lead\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MergeDuplicateLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:merge-duplicates 
                            {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}
                            {--campaign= : ID de campaña específica para procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consolida leads duplicados con el mismo teléfono y campaña';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $campaignId = $this->option('campaign');

        $this->info('🔍 Buscando leads duplicados...');
        $this->newLine();

        // Buscar duplicados agrupados por phone + campaign_id
        $query = Lead::select('phone', 'campaign_id', DB::raw('COUNT(*) as count'))
            ->groupBy('phone', 'campaign_id')
            ->having('count', '>', 1);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $duplicates = $query->get();

        if ($duplicates->isEmpty()) {
            $this->info('✅ No se encontraron leads duplicados');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Encontrados {$duplicates->count()} grupos de leads duplicados");
        $this->newLine();

        $totalMerged = 0;
        $totalRemoved = 0;

        foreach ($duplicates as $duplicate) {
            // Obtener todos los leads con este teléfono + campaña
            $leads = Lead::where('phone', $duplicate->phone)
                ->where('campaign_id', $duplicate->campaign_id)
                ->with(['interactions', 'campaign'])
                ->orderBy('created_at', 'asc') // El más antiguo primero
                ->get();

            if ($leads->count() < 2) {
                continue;
            }

            $this->line("📞 Teléfono: {$duplicate->phone} | Campaña: {$leads->first()->campaign->name}");
            $this->line("   Duplicados encontrados: {$leads->count()}");

            // Determinar el lead principal (el más antiguo)
            $mainLead = $leads->first();
            $duplicateLeads = $leads->slice(1);

            $this->line("   → Lead principal: {$mainLead->id} ({$mainLead->name})");

            if ($dryRun) {
                $this->line("   🔶 [DRY-RUN] Se consolidarían las interacciones de:");
                foreach ($duplicateLeads as $dup) {
                    $interactionsCount = $dup->interactions->count();
                    $this->line("      - {$dup->id} ({$dup->name}) - {$interactionsCount} interacciones");
                }
                $this->newLine();
                continue;
            }

            // Consolidar interacciones de duplicados al lead principal
            DB::transaction(function () use ($mainLead, $duplicateLeads, &$totalRemoved) {
                foreach ($duplicateLeads as $dupLead) {
                    $interactionsCount = $dupLead->interactions->count();

                    // Mover interacciones al lead principal
                    if ($interactionsCount > 0) {
                        $dupLead->interactions()->update(['lead_id' => $mainLead->id]);
                        $this->line("      ✓ Migradas {$interactionsCount} interacciones de {$dupLead->id}");
                    }

                    // Actualizar datos del lead principal si el duplicado tiene mejor información
                    $updates = [];

                    // Si el duplicado tiene nombre y el principal no
                    if ($dupLead->name && (!$mainLead->name || str_contains(strtolower($mainLead->name), 'lead desde'))) {
                        $updates['name'] = $dupLead->name;
                    }

                    // Si el duplicado tiene ciudad y el principal no
                    if ($dupLead->city && !$mainLead->city) {
                        $updates['city'] = $dupLead->city;
                    }

                    // Si el duplicado tiene intención y el principal no
                    if ($dupLead->intention && !$mainLead->intention) {
                        $updates['intention'] = $dupLead->intention;
                        $updates['intention_status'] = $dupLead->intention_status;
                        $updates['intention_origin'] = $dupLead->intention_origin;
                    }

                    // Si el duplicado tiene notas, agregarlas
                    if ($dupLead->notes) {
                        $existingNotes = $mainLead->notes ?? '';
                        $updates['notes'] = $existingNotes
                            ? $existingNotes . "\n\n[Merged from {$dupLead->id}]: " . $dupLead->notes
                            : $dupLead->notes;
                    }

                    if (!empty($updates)) {
                        $mainLead->update($updates);
                        $this->line("      ✓ Actualizada información del lead principal");
                    }

                    // Eliminar el lead duplicado
                    $dupLead->delete();
                    $totalRemoved++;
                }
            });

            $totalMerged++;
            $this->line("   ✅ Consolidado exitosamente");
            $this->newLine();

            Log::info('Leads duplicados consolidados', [
                'main_lead_id' => $mainLead->id,
                'phone' => $mainLead->phone,
                'campaign_id' => $mainLead->campaign_id,
                'removed_count' => $duplicateLeads->count(),
            ]);
        }

        $this->newLine();
        $this->info("✨ Proceso completado");
        $this->info("   Grupos consolidados: {$totalMerged}");
        $this->info("   Leads eliminados: {$totalRemoved}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔶 Modo DRY-RUN activado: No se realizaron cambios');
            $this->line('   Ejecuta sin --dry-run para aplicar los cambios');
        }

        return Command::SUCCESS;
    }
}
