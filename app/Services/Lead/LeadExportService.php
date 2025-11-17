<?php

namespace App\Services\Lead;

use App\Enums\ExportRule;
use App\Enums\LeadIntentionStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Services\Webhook\WebhookDispatcherService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para exportar leads al cliente
 * según las reglas de la campaña
 */
class LeadExportService
{
    public function __construct(
        private LeadRepositoryInterface $leadRepository,
        private WebhookDispatcherService $webhookDispatcher,
    ) {}

    /**
     * Exportar lead al cliente según reglas de campaña
     */
    public function exportLead(Lead $lead): bool
    {
        if (! $lead->campaign) {
            Log::warning('Intento de exportar lead sin campaña', ['lead_id' => $lead->id]);

            return false;
        }

        // Verificar si el lead debe ser exportado según las reglas
        if (! $this->shouldExportLead($lead)) {
            Log::info('Lead no cumple criterios de exportación', [
                'lead_id' => $lead->id,
                'campaign_id' => $lead->campaign_id,
                'intention' => $lead->intention,
                'export_rule' => $lead->campaign->export_rule?->value,
            ]);

            return false;
        }

        // Enviar al webhook del cliente si está configurado
        return $this->sendToClientWebhook($lead);
    }

    /**
     * Verificar si un lead debe ser exportado según reglas de campaña
     */
    public function shouldExportLead(Lead $lead): bool
    {
        $campaign = $lead->campaign;

        // Verificar que la intención esté finalizada
        if ($lead->intention_status !== LeadIntentionStatus::FINALIZED) {
            return false;
        }

        // Regla NONE: nunca exportar
        if ($campaign->export_rule === ExportRule::NONE) {
            return false;
        }

        // Regla INTERESTED_ONLY: solo interesados
        if ($campaign->export_rule === ExportRule::INTERESTED_ONLY) {
            return $lead->intention === 'interested';
        }

        // Regla NOT_INTERESTED_ONLY: solo no interesados
        if ($campaign->export_rule === ExportRule::NOT_INTERESTED_ONLY) {
            return $lead->intention === 'not_interested';
        }

        // Regla BOTH: ambos tipos (interesados y no interesados)
        if ($campaign->export_rule === ExportRule::BOTH) {
            return in_array($lead->intention, ['interested', 'not_interested']);
        }

        // Por defecto, solo exportar interesados
        return $lead->intention === 'interested';
    }

    /**
     * Enviar lead al webhook del cliente
     */
    private function sendToClientWebhook(Lead $lead): bool
    {
        try {
            // Obtener source con webhook configurado
            $sources = $lead->campaign->client->sources()
                ->where('status', 'active')
                ->whereNotNull('webhook_url')
                ->get();

            if ($sources->isEmpty()) {
                Log::warning('No hay sources con webhook configurado para el cliente', [
                    'lead_id' => $lead->id,
                    'client_id' => $lead->campaign->client_id,
                ]);

                return false;
            }

            // Enviar a cada source (normalmente será 1)
            $success = false;
            foreach ($sources as $source) {
                $sent = $this->webhookDispatcher->dispatchLeadToClient($lead, $source);
                if ($sent) {
                    $success = true;
                }
            }

            // Marcar lead como exportado
            if ($success) {
                $lead->update([
                    'intention_status' => LeadIntentionStatus::SENT_TO_CLIENT,
                    'exported_at' => now(),
                ]);

                Log::info('Lead exportado exitosamente al cliente', [
                    'lead_id' => $lead->id,
                    'campaign_id' => $lead->campaign_id,
                    'intention' => $lead->intention,
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('Error exportando lead al cliente', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Exportar leads en lote
     */
    public function exportLeads(Collection $leads): array
    {
        $results = [
            'exported' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($leads as $lead) {
            try {
                if ($this->exportLead($lead)) {
                    $results['exported']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Error en exportación masiva de lead', [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Obtener leads pendientes de exportación para una campaña
     */
    public function getPendingExportLeads(Campaign $campaign): Collection
    {
        return $this->leadRepository->findBy([
            'campaign_id' => $campaign->id,
            'intention_status' => LeadIntentionStatus::FINALIZED,
        ])->filter(fn ($lead) => $this->shouldExportLead($lead));
    }

    /**
     * Obtener leads ya exportados de una campaña
     */
    public function getExportedLeads(Campaign $campaign): Collection
    {
        return $this->leadRepository->findBy([
            'campaign_id' => $campaign->id,
            'intention_status' => LeadIntentionStatus::SENT_TO_CLIENT,
        ]);
    }

    /**
     * Obtener estadísticas de exportación para una campaña
     */
    public function getExportStats(Campaign $campaign): array
    {
        $allLeads = $campaign->leads;

        $finalized = $allLeads->where('intention_status', LeadIntentionStatus::FINALIZED);
        $exported = $allLeads->where('intention_status', LeadIntentionStatus::SENT_TO_CLIENT);
        $pending = $allLeads->where('intention_status', LeadIntentionStatus::PENDING);

        return [
            'total_leads' => $allLeads->count(),
            'pending' => $pending->count(),
            'finalized' => $finalized->count(),
            'exported' => $exported->count(),
            'by_intention' => [
                'interested' => $finalized->where('intention', 'interested')->count(),
                'not_interested' => $finalized->where('intention', 'not_interested')->count(),
                'no_response' => $finalized->where('intention', 'no_response')->count(),
            ],
            'exportable' => $finalized->filter(fn ($lead) => $this->shouldExportLead($lead))->count(),
        ];
    }
}
