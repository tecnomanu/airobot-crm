<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Source;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para enviar mensajes de WhatsApp usando Evolution API
 */
class WhatsAppSenderService
{
    /**
     * Enviar mensaje de WhatsApp
     */
    public function sendMessage(Source $source, Lead $lead, string $message): array
    {
        // Obtener configuración del source
        $apiUrl = $source->getConfigValue('api_url');
        $apiKey = $source->getConfigValue('api_key');
        $instanceName = $source->getConfigValue('instance_name');

        if (!$apiUrl || !$apiKey || !$instanceName) {
            throw new \Exception('Configuración de WhatsApp incompleta en la fuente');
        }

        // Normalizar teléfono (quitar + y espacios)
        $phone = str_replace(['+', ' ', '-'], '', $lead->phone);

        // Preparar payload para Evolution API
        $payload = [
            'number' => $phone,
            'text' => $message,
        ];

        Log::info('Enviando mensaje WhatsApp vía Evolution API', [
            'lead_id' => $lead->id,
            'phone' => $phone,
            'instance' => $instanceName,
            'api_url' => $apiUrl,
        ]);

        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$apiUrl}/message/sendText/{$instanceName}", $payload);

            if ($response->successful()) {
                Log::info('Mensaje WhatsApp enviado exitosamente', [
                    'lead_id' => $lead->id,
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('key.id'),
                    'response' => $response->json(),
                ];
            }

            Log::error('Error al enviar mensaje WhatsApp', [
                'lead_id' => $lead->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \Exception('Error al enviar mensaje: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Excepción al enviar mensaje WhatsApp', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verificar estado de una instancia de WhatsApp
     */
    public function checkInstanceStatus(Source $source): array
    {
        $apiUrl = $source->getConfigValue('api_url');
        $apiKey = $source->getConfigValue('api_key');
        $instanceName = $source->getConfigValue('instance_name');

        if (!$apiUrl || !$apiKey || !$instanceName) {
            return [
                'success' => false,
                'error' => 'Configuración incompleta',
            ];
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
            ])->get("{$apiUrl}/instance/connectionState/{$instanceName}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'state' => $response->json('state'),
                    'instance' => $response->json('instance'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

