<?php

declare(strict_types=1);

namespace App\Services\External;

use App\DTOs\External\WebhookResultDTO;
use App\Enums\SourceType;
use App\Models\Lead;
use App\Models\Source;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementación de envío de webhooks HTTP
 */
class HttpWebhookSender implements WebhookSenderInterface
{
    /**
     * {@inheritDoc}
     */
    public function sendLeadToDestination(
        Source $source,
        Lead $lead,
        array $extraPayload = []
    ): WebhookResultDTO {
        // Validar que la fuente sea de tipo WEBHOOK
        if ($source->type !== SourceType::WEBHOOK) {
            throw new \InvalidArgumentException(
                "Source debe ser de tipo WEBHOOK. Tipo actual: {$source->type->label()}"
            );
        }

        // Obtener configuración
        $url = $source->getConfigValue('url');
        $method = strtoupper($source->getConfigValue('method', 'POST'));
        $secret = $source->getConfigValue('secret');

        if (! $url) {
            throw new \InvalidArgumentException('Source Webhook no tiene URL configurada');
        }

        // Construir payload
        $payload = $this->buildPayload($lead, $extraPayload, $source);

        Log::info('Enviando lead a webhook externo', [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'lead_id' => $lead->id,
            'url' => $url,
            'method' => $method,
        ]);

        try {
            $request = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'AIRobot/1.0',
                    'X-Lead-ID' => (string) $lead->id,
                    'X-Campaign-ID' => (string) $lead->campaign_id,
                    'Content-Type' => 'application/json',
                ]);

            // Agregar secret como header si existe
            if ($secret) {
                $request->withHeaders([
                    'X-Webhook-Secret' => $secret,
                ]);
            }

            // Realizar petición según método
            $response = match ($method) {
                'POST' => $request->post($url, $payload),
                'PUT' => $request->put($url, $payload),
                'PATCH' => $request->patch($url, $payload),
                'GET' => $request->get($url, $payload),
                default => throw new \InvalidArgumentException("Método HTTP no soportado: {$method}"),
            };

            $success = $response->successful();
            $statusCode = $response->status();
            $responseBody = $response->body();

            if ($success) {
                Log::info('Lead enviado exitosamente a webhook', [
                    'source_id' => $source->id,
                    'lead_id' => $lead->id,
                    'status_code' => $statusCode,
                ]);

                return WebhookResultDTO::success($statusCode, $responseBody);
            } else {
                Log::warning('Webhook retornó error', [
                    'source_id' => $source->id,
                    'lead_id' => $lead->id,
                    'status_code' => $statusCode,
                    'response' => substr($responseBody, 0, 200),
                ]);

                return WebhookResultDTO::failed(
                    "HTTP {$statusCode}: ".substr($responseBody, 0, 200),
                    $statusCode
                );
            }

        } catch (\Exception $e) {
            Log::error('Error enviando lead a webhook', [
                'source_id' => $source->id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return WebhookResultDTO::failed($e->getMessage());
        }
    }

    /**
     * Construir payload para el webhook
     */
    protected function buildPayload(Lead $lead, array $extraPayload, Source $source): array
    {
        // Cargar relaciones necesarias
        $lead->loadMissing('campaign');

        // Payload base
        $basePayload = [
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
                'id' => $lead->campaign->id,
                'name' => $lead->campaign->name,
            ],
            'sent_at' => now()->toIso8601String(),
        ];

        // Si la Source tiene un payload_template personalizado en config, usarlo
        $payloadTemplate = $source->getConfigValue('payload_template');

        if ($payloadTemplate) {
            return $this->applyTemplate($lead, $payloadTemplate, $extraPayload);
        }

        // Merge con payload extra
        return array_merge($basePayload, $extraPayload);
    }

    /**
     * Aplicar template personalizado al payload
     */
    protected function applyTemplate(Lead $lead, string $template, array $extraPayload): array
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

        $result = $decoded ?? ['data' => $replaced];

        // Merge con extra payload
        return array_merge($result, $extraPayload);
    }
}
