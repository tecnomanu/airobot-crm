<?php

namespace App\Jobs\Lead;

use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job para verificar leads con intenciones pendientes
 * Si no han respondido en X horas, marcar como "no_response"
 */
class CheckPendingIntentsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Timeout en horas para considerar "no respuesta"
     * Configurable por campaña en el futuro
     */
    private int $timeoutHours;

    /**
     * Create a new job instance.
     */
    public function __construct(int $timeoutHours = 24)
    {
        $this->timeoutHours = $timeoutHours;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoffTime = now()->subHours($this->timeoutHours);

        Log::info('Iniciando verificación de intenciones pendientes', [
            'timeout_hours' => $this->timeoutHours,
            'cutoff_time' => $cutoffTime->toDateTimeString(),
        ]);

        // Buscar leads con intención pendiente
        $pendingLeads = Lead::where('intention_status', LeadIntentionStatus::PENDING)
            ->whereNotNull('intention_origin')
            ->with(['interactions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->get();

        $processed = 0;
        $noResponse = 0;

        foreach ($pendingLeads as $lead) {
            if ($this->hasTimedOut($lead, $cutoffTime)) {
                $this->markAsNoResponse($lead);
                $noResponse++;
            }
            $processed++;
        }

        Log::info('Verificación de intenciones completada', [
            'total_processed' => $processed,
            'marked_no_response' => $noResponse,
        ]);
    }

    /**
     * Verificar si el lead ha excedido el timeout sin responder
     */
    private function hasTimedOut(Lead $lead, $cutoffTime): bool
    {
        // Obtener última interacción outbound (envío de WhatsApp)
        $lastOutbound = $lead->interactions()
            ->where('channel', InteractionChannel::WHATSAPP)
            ->where('direction', InteractionDirection::OUTBOUND)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastOutbound) {
            // No hay mensaje saliente, no aplicar timeout
            return false;
        }

        // Verificar si el mensaje saliente es anterior al cutoff time
        if ($lastOutbound->created_at->isAfter($cutoffTime)) {
            // Mensaje enviado hace menos del timeout, aún no procesar
            return false;
        }

        // Verificar si hay respuesta inbound después del outbound
        $hasInboundAfter = $lead->interactions()
            ->where('channel', InteractionChannel::WHATSAPP)
            ->where('direction', InteractionDirection::INBOUND)
            ->where('created_at', '>', $lastOutbound->created_at)
            ->exists();

        // Si no hay respuesta inbound y ya pasó el timeout, marcar como no respuesta
        return ! $hasInboundAfter;
    }

    /**
     * Marcar lead como "no responde"
     */
    private function markAsNoResponse(Lead $lead): void
    {
        $lead->update([
            'intention' => 'no_response',
            'intention_status' => LeadIntentionStatus::FINALIZED,
            'intention_decided_at' => now(),
            'status' => LeadStatus::INVALID,
        ]);

        Log::info('Lead marcado como no responde por timeout', [
            'lead_id' => $lead->id,
            'phone' => $lead->phone,
            'campaign_id' => $lead->campaign_id,
        ]);
    }
}
