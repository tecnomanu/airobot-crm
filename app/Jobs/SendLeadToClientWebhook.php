<?php

namespace App\Jobs;

use App\Enums\SourceStatus;
use App\Models\Lead;
use App\Services\External\WebhookSenderInterface;
use App\Services\Lead\LeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar lead a webhook del cliente
 *
 * NOTA: Ahora soporta tanto Sources (nuevo) como campos legacy de Campaign.
 * Se prioriza webhookSource si está configurado.
 */
class SendLeadToClientWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos
     */
    public $tries = 3;

    /**
     * Tiempo de espera entre reintentos (en segundos)
     */
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Timeout para el job
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Lead $lead,
        public ?array $customPayload = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        LeadService $leadService,
        WebhookSenderInterface $webhookSender
    ): void {
        $campaign = $this->lead->campaign;

        // NUEVA LÓGICA: Priorizar webhookSource si existe
        if ($campaign->webhookSource) {
            $this->handleWithSource($campaign->webhookSource, $leadService, $webhookSender);

            return;
        }

        // LEGACY: Usar campos directos de Campaign
        // TODO: Deprecar una vez migradas todas las campañas a Sources
        Log::warning('Usando configuración legacy de webhook (campos directos en Campaign)', [
            'lead_id' => $this->lead->id,
            'campaign_id' => $campaign->id,
        ]);

        $this->handleLegacy($campaign, $leadService);
    }

    /**
     * Manejar envío usando Source (NUEVA LÓGICA)
     */
    protected function handleWithSource($source, LeadService $leadService, WebhookSenderInterface $webhookSender): void
    {
        // Validar que la fuente esté activa
        if ($source->status !== SourceStatus::ACTIVE) {
            Log::warning('Source de webhook no está activa', [
                'lead_id' => $this->lead->id,
                'source_id' => $source->id,
                'status' => $source->status->value,
            ]);

            return;
        }

        Log::info('Enviando lead a webhook usando Source', [
            'lead_id' => $this->lead->id,
            'source_id' => $source->id,
            'source_name' => $source->name,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Enviar usando el servicio
            $result = $webhookSender->sendLeadToDestination(
                $source,
                $this->lead,
                $this->customPayload ?? []
            );

            // Registrar resultado
            $leadService->markWebhookSent($this->lead->id, json_encode($result->toArray()));

            if ($result->success) {
                Log::info('Lead enviado exitosamente a webhook via Source', [
                    'lead_id' => $this->lead->id,
                    'source_id' => $source->id,
                    'status_code' => $result->statusCode,
                    'attempt' => $this->attempts(),
                ]);
            } else {
                Log::warning('Webhook via Source retornó error', [
                    'lead_id' => $this->lead->id,
                    'source_id' => $source->id,
                    'error' => $result->error,
                    'attempt' => $this->attempts(),
                ]);

                // Reintentar si no es el último intento
                if ($this->attempts() < $this->tries) {
                    throw new \Exception($result->error);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error enviando lead a webhook via Source', [
                'lead_id' => $this->lead->id,
                'source_id' => $source->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-lanzar para reintentar si no es el último intento
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Manejar envío usando campos legacy de Campaign
     */
    protected function handleLegacy($campaign, LeadService $leadService): void
    {
        // Validar que el webhook esté habilitado
        if (! $campaign->webhook_enabled) {
            Log::info('Webhook no habilitado para campaña (legacy)', [
                'lead_id' => $this->lead->id,
                'campaign_id' => $campaign->id,
            ]);

            return;
        }

        // Validar que haya URL de webhook
        if (empty($campaign->webhook_url)) {
            Log::warning('URL de webhook vacía (legacy)', [
                'lead_id' => $this->lead->id,
                'campaign_id' => $campaign->id,
            ]);

            return;
        }

        try {
            // Preparar payload
            $payload = $this->customPayload ?? $this->buildPayload($this->lead, $campaign);

            Log::info('Enviando lead a webhook del cliente', [
                'lead_id' => $this->lead->id,
                'webhook_url' => $campaign->webhook_url,
                'method' => $campaign->webhook_method->value,
                'attempt' => $this->attempts(),
            ]);

            // Realizar llamada HTTP
            $response = Http::timeout(config('webhooks.timeout', 30))
                ->withHeaders([
                    'User-Agent' => 'AIRobot/1.0',
                    'X-Lead-ID' => $this->lead->id,
                ])
                ->{strtolower($campaign->webhook_method->value)}($campaign->webhook_url, $payload);

            $success = $response->successful();
            $statusCode = $response->status();
            $responseBody = $response->body();

            // Registrar resultado
            $result = [
                'status_code' => $statusCode,
                'body' => strlen($responseBody) > 500 ? substr($responseBody, 0, 500).'...' : $responseBody,
                'sent_at' => now()->toIso8601String(),
                'attempt' => $this->attempts(),
            ];

            $leadService->markWebhookSent($this->lead->id, json_encode($result));

            if ($success) {
                Log::info('Lead enviado exitosamente a webhook del cliente', [
                    'lead_id' => $this->lead->id,
                    'status_code' => $statusCode,
                    'attempt' => $this->attempts(),
                ]);
            } else {
                Log::warning('Webhook del cliente devolvió error', [
                    'lead_id' => $this->lead->id,
                    'status_code' => $statusCode,
                    'response' => substr($responseBody, 0, 200),
                    'attempt' => $this->attempts(),
                ]);

                // Si no es exitoso y no es el último intento, lanzar excepción para reintentar
                if ($this->attempts() < $this->tries) {
                    throw new \Exception("Webhook returned HTTP {$statusCode}");
                }
            }

        } catch (\Exception $e) {
            Log::error('Error enviando lead a webhook del cliente', [
                'lead_id' => $this->lead->id,
                'webhook_url' => $campaign->webhook_url,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Registrar error
            $errorResult = [
                'error' => $e->getMessage(),
                'sent_at' => now()->toIso8601String(),
                'attempt' => $this->attempts(),
            ];

            $leadService->markWebhookSent($this->lead->id, json_encode($errorResult));

            // Re-lanzar para reintentar si no es el último intento
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Construir payload para el webhook del cliente
     */
    private function buildPayload(Lead $lead, $campaign): array
    {
        // Si hay template personalizado, usarlo
        if (! empty($campaign->webhook_payload_template)) {
            return $this->applyTemplate($lead, $campaign->webhook_payload_template);
        }

        // Payload por defecto
        return [
            'id' => $lead->id,
            'phone' => $lead->phone,
            'name' => $lead->name,
            'city' => $lead->city,
            'option_selected' => $lead->option_selected?->value,
            'status' => $lead->status->value,
            'source' => $lead->source->value,
            'intention' => $lead->intention,
            'notes' => $lead->notes,
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
            ],
            'sent_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Aplicar template de payload
     */
    private function applyTemplate(Lead $lead, string $template): array
    {
        // Reemplazar variables en el template
        $replaced = str_replace([
            '{{id}}', '{{lead_id}}',
            '{{phone}}',
            '{{name}}',
            '{{city}}',
            '{{option_selected}}',
            '{{status}}',
            '{{source}}',
            '{{intention}}',
            '{{notes}}',
            '{{campaign_id}}',
            '{{campaign_name}}',
        ], [
            $lead->id, $lead->id,
            $lead->phone,
            $lead->name ?? '',
            $lead->city ?? '',
            $lead->option_selected?->value ?? '',
            $lead->status->value,
            $lead->source->value,
            $lead->intention ?? '',
            $lead->notes ?? '',
            $lead->campaign->id,
            $lead->campaign->name,
        ], $template);

        // Intentar decodificar como JSON
        $decoded = json_decode($replaced, true);

        return $decoded ?? ['data' => $replaced];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de envío de lead al cliente falló definitivamente', [
            'lead_id' => $this->lead->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
