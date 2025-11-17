<?php

namespace App\Services\Webhook;

use App\Jobs\SendLeadToClientWebhook;
use App\Models\Lead;
use App\Models\Source;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para despachar webhooks a clientes
 */
class WebhookDispatcherService
{
    /**
     * Enviar lead al webhook del cliente
     */
    public function dispatchLeadToClient(Lead $lead, Source $source): bool
    {
        try {
            if (! $source->webhook_url) {
                Log::warning('Source no tiene webhook_url configurado', [
                    'source_id' => $source->id,
                    'lead_id' => $lead->id,
                ]);

                return false;
            }

            // Despachar job para enviar webhook
            SendLeadToClientWebhook::dispatch($lead);

            Log::info('Webhook despachado a cliente', [
                'lead_id' => $lead->id,
                'source_id' => $source->id,
                'webhook_url' => $source->webhook_url,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error despachando webhook a cliente', [
                'lead_id' => $lead->id,
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

