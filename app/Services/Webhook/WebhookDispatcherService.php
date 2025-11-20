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

    /**
     * Enviar lead a webhook de intención según su intención detectada
     */
    public function dispatchLeadIntentionWebhook(Lead $lead): bool
    {
        try {
            $campaign = $lead->campaign;

            if (! $campaign) {
                Log::warning('Lead no tiene campaña asociada para webhook de intención', [
                    'lead_id' => $lead->id,
                ]);
                return false;
            }

            // Determinar qué webhook usar según la intención
            $webhookSource = null;
            $isInterested = false;

            if ($lead->intention === 'interested' && $campaign->send_intention_interested_webhook) {
                $webhookSource = $campaign->intentionInterestedWebhook;
                $isInterested = true;
            } elseif ($lead->intention === 'not_interested' && $campaign->send_intention_not_interested_webhook) {
                $webhookSource = $campaign->intentionNotInterestedWebhook;
                $isInterested = false;
            }

            if (! $webhookSource) {
                Log::info('No hay webhook configurado para esta intención', [
                    'lead_id' => $lead->id,
                    'intention' => $lead->intention,
                ]);
                return false;
            }

            if (! $webhookSource->webhook_url) {
                Log::warning('Webhook de intención no tiene URL configurada', [
                    'source_id' => $webhookSource->id,
                    'lead_id' => $lead->id,
                ]);
                return false;
            }

            // Enviar el webhook de forma síncrona con HTTP Client
            $response = \Illuminate\Support\Facades\Http::post($webhookSource->webhook_url, [
                'lead_id' => $lead->id,
                'phone' => $lead->phone,
                'name' => $lead->name,
                'intention' => $lead->intention,
                'intention_status' => $lead->intention_status?->value,
                'intention_origin' => $lead->intention_origin?->value,
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'city' => $lead->city,
                'country' => $lead->country,
                'option_selected' => $lead->option_selected,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Actualizar el lead con el resultado
            $lead->update([
                'intention_webhook_sent' => true,
                'intention_webhook_sent_at' => now(),
                'intention_webhook_status' => $response->successful() ? 'success' : 'failed',
                'intention_webhook_response' => $response->body(),
            ]);

            if ($response->successful()) {
                Log::info('Webhook de intención enviado exitosamente', [
                    'lead_id' => $lead->id,
                    'intention' => $lead->intention,
                    'webhook_url' => $webhookSource->webhook_url,
                    'status_code' => $response->status(),
                ]);
                return true;
            } else {
                Log::error('Webhook de intención falló', [
                    'lead_id' => $lead->id,
                    'intention' => $lead->intention,
                    'webhook_url' => $webhookSource->webhook_url,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            // Guardar error en el lead
            $lead->update([
                'intention_webhook_sent' => true,
                'intention_webhook_sent_at' => now(),
                'intention_webhook_status' => 'failed',
                'intention_webhook_response' => $e->getMessage(),
            ]);

            Log::error('Error enviando webhook de intención', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
