<?php

declare(strict_types=1);

namespace App\Services\External;

use App\Enums\SourceType;
use App\Models\Lead;
use App\Models\Source;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementación de envío de WhatsApp mediante Evolution API
 */
class EvolutionWhatsAppSender implements WhatsAppSenderInterface
{
    /**
     * {@inheritDoc}
     */
    public function sendMessage(
        Source $source, 
        Lead $lead, 
        string $body, 
        array $attachments = []
    ): array {
        // Validar que la fuente sea de WhatsApp
        if (!$source->type->isMessaging()) {
            throw new \InvalidArgumentException(
                "Source debe ser de tipo WhatsApp. Tipo actual: {$source->type->label()}"
            );
        }

        // Obtener config de la fuente
        $apiUrl = $source->getConfigValue('api_url');
        $apiKey = $source->getConfigValue('api_key');
        $instanceName = $source->getConfigValue('instance_name');

        if (!$apiUrl || !$apiKey || !$instanceName) {
            throw new \InvalidArgumentException(
                'Source WhatsApp no tiene configuración completa (api_url, api_key, instance_name)'
            );
        }

        Log::info('Enviando mensaje WhatsApp via Evolution API', [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'lead_id' => $lead->id,
            'phone' => $lead->phone,
        ]);

        // Construir endpoint según Evolution API
        // Formato típico: {base_url}/message/sendText/{instance}
        $endpoint = rtrim($apiUrl, '/') . "/message/sendText/{$instanceName}";

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'number' => $lead->phone,
                    'textMessage' => [
                        'text' => $body,
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception(
                    "Evolution API error: HTTP {$response->status()} - {$response->body()}"
                );
            }

            $result = $response->json();

            Log::info('Mensaje WhatsApp enviado exitosamente', [
                'source_id' => $source->id,
                'lead_id' => $lead->id,
                'response' => $result,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Error enviando mensaje WhatsApp', [
                'source_id' => $source->id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendTemplate(
        Source $source,
        Lead $lead,
        string $templateName,
        array $variables = []
    ): array {
        // Validar fuente
        if (!$source->type->isMessaging()) {
            throw new \InvalidArgumentException(
                "Source debe ser de tipo WhatsApp. Tipo actual: {$source->type->label()}"
            );
        }

        $apiUrl = $source->getConfigValue('api_url');
        $apiKey = $source->getConfigValue('api_key');
        $instanceName = $source->getConfigValue('instance_name');

        if (!$apiUrl || !$apiKey || !$instanceName) {
            throw new \InvalidArgumentException(
                'Source WhatsApp no tiene configuración completa'
            );
        }

        Log::info('Enviando template WhatsApp via Evolution API', [
            'source_id' => $source->id,
            'lead_id' => $lead->id,
            'template' => $templateName,
        ]);

        // TODO: Implementar según la API específica de templates
        // Evolution API puede tener un endpoint diferente para templates
        throw new \Exception('Envío de templates no implementado aún');
    }
}

