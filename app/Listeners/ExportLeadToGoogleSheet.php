<?php

namespace App\Listeners;

use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStatus;
use App\Events\LeadUpdated;
use App\Jobs\ExportLeadToGoogleSheetJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ExportLeadToGoogleSheet
{
    /**
     * Handle the event.
     */
    public function handle(LeadUpdated $event): void
    {
        $lead = $event->lead;

        // Validar si es momento de exportar
        // Criterio: Está "Listo para vender" (Intention finalized) 
        // Opcional: También podríamos chequear si status es CONTACTED o algo específico,
        // pero LeadIntentionStatus::FINALIZED suele ser el indicador fuerte.
        // Y EVITAR duplicados si ya se exportó (aunque el Job verifica IDs, mejor filtrar aquí también)
        
        $isSalesReady = $lead->intention_status === LeadIntentionStatus::FINALIZED;
        
        // También podemos soportar un estado explícito si el usuario prefiere eso
        // $isReadyStatus = $lead->status === LeadStatus::READY_TO_SELL; 
        
        if ($isSalesReady && !$lead->exported_at) {
            Log::info("Lead {$lead->id} is sales ready. Dispatching export job.");
            ExportLeadToGoogleSheetJob::dispatch($lead);
        }
    }
}
