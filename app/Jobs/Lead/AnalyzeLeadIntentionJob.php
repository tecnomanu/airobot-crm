<?php

namespace App\Jobs\Lead;

use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Models\Lead\Lead;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Services\Lead\LeadIntentionAnalyzerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Job para analizar intención de lead con IA de forma asíncrona
 *
 * Espera unos segundos antes de analizar para permitir que lleguen
 * múltiples mensajes del lead y analizarlos todos juntos con contexto.
 */
class AnalyzeLeadIntentionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 60;

    /**
     * @param  string  $leadId  ID del lead
     * @param  int  $expectedVersion  Versión esperada para evitar procesamiento duplicado
     */
    public function __construct(
        private string $leadId,
        private int $expectedVersion
    ) {}

    public function handle(
        LeadRepositoryInterface $leadRepository,
        LeadIntentionAnalyzerService $aiAnalyzer
    ): void {
        // Verificar que la versión sea la esperada (si llegó otro mensaje, se canceló este job)
        $cacheKey = "lead_intention_analysis:{$this->leadId}";
        $currentVersion = Cache::get($cacheKey, 0);

        if ($currentVersion !== $this->expectedVersion) {
            Log::info('Job de análisis cancelado (versión desactualizada)', [
                'lead_id' => $this->leadId,
                'expected_version' => $this->expectedVersion,
                'current_version' => $currentVersion,
            ]);

            return;
        }

        $lead = Lead::with(['messages' => function ($query) {
            $query->where('channel', 'whatsapp')
                ->where('direction', 'inbound')
                ->orderBy('created_at', 'desc')
                ->limit(10); // Últimos 10 mensajes para contexto
        }])->find($this->leadId);

        if (! $lead) {
            Log::warning('Lead no encontrado para análisis de intención', [
                'lead_id' => $this->leadId,
            ]);

            return;
        }

        // Si ya tiene intención finalizada, no procesar
        if ($lead->intention_status === LeadIntentionStatus::FINALIZED) {
            Log::info('Lead ya tiene intención finalizada, saltando análisis', [
                'lead_id' => $this->leadId,
                'intention' => $lead->intention,
            ]);
            Cache::forget($cacheKey);

            return;
        }

        // Si no hay mensajes, no hay nada que analizar
        if ($lead->messages->isEmpty()) {
            Log::info('No hay mensajes para analizar', [
                'lead_id' => $this->leadId,
            ]);
            Cache::forget($cacheKey);

            return;
        }

        // Construir contexto de mensajes
        $messages = $lead->messages
            ->reverse() // Orden cronológico
            ->pluck('content')
            ->filter()
            ->toArray();

        $campaignContext = $lead->campaign?->name;

        // Analizar con IA
        Log::info('Iniciando análisis de intención con IA', [
            'lead_id' => $this->leadId,
            'messages_count' => count($messages),
            'campaign' => $campaignContext,
        ]);

        $detectedIntention = $aiAnalyzer->analyzeIntentionWithContext($messages, $campaignContext);

        if ($detectedIntention) {
            // Actualizar lead con intención detectada
            $leadRepository->update($lead, [
                'intention' => $detectedIntention,
                'intention_status' => LeadIntentionStatus::FINALIZED,
                'intention_decided_at' => now(),
                'intention_origin' => LeadIntentionOrigin::AGENT_IA,
            ]);

            Log::info('Intención detectada por IA y actualizada', [
                'lead_id' => $this->leadId,
                'intention' => $detectedIntention,
            ]);

            // Disparar webhook si es necesario
            $this->dispatchIntentionWebhook($lead, $detectedIntention);
        } else {
            Log::info('IA no pudo determinar intención clara', [
                'lead_id' => $this->leadId,
            ]);
        }

        // Limpiar cache
        Cache::forget($cacheKey);
    }

    /**
     * Disparar webhook de intención detectada
     */
    protected function dispatchIntentionWebhook(Lead $lead, string $intention): void
    {
        try {
            $webhookDispatcher = app(\App\Services\Webhook\WebhookDispatcherService::class);
            $webhookDispatcher->dispatchLeadIntentionWebhook($lead);
        } catch (\Exception $e) {
            Log::error('Error al enviar webhook de intención desde Job', [
                'lead_id' => $lead->id,
                'intention' => $intention,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
