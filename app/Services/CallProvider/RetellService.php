<?php

namespace App\Services\CallProvider;

use App\Models\Campaign\CampaignCallAgent;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para interactuar con la API de Retell AI
 * Maneja llamadas telefónicas, agentes y números telefónicos
 */
class RetellService
{
    private ?string $apiKey = null;
    private string $baseUrl = 'https://api.retellai.com';

    public function __construct()
    {
        $apiKey = config('services.retell.api_key') ?? env('RETELL_API_KEY');

        if (empty($apiKey)) {
            throw new \RuntimeException(
                'RETELL_API_KEY no está configurada. Por favor, configura la variable de entorno RETELL_API_KEY en tu archivo .env'
            );
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Crear una llamada telefónica saliente
     *
     * @param  string  $fromNumber  Número origen en formato E.164 (ej: +14157774444)
     * @param  string  $toNumber  Número destino en formato E.164 (ej: +12137774445)
     * @param  array  $options  Opciones adicionales:
     *                         - override_agent_id: ID del agente a usar
     *                         - override_agent_version: Versión del agente
     *                         - agent_override: Configuración completa del agente
     *                         - metadata: Metadatos arbitrarios
     *                         - retell_llm_dynamic_variables: Variables dinámicas para el LLM
     *                         - custom_sip_headers: Headers SIP personalizados
     * @return array Respuesta de la API con información de la llamada
     *
     * @throws \Exception Si la llamada falla
     */
    public function createPhoneCall(string $fromNumber, string $toNumber, array $options = []): array
    {
        $payload = array_merge([
            'from_number' => $fromNumber,
            'to_number' => $toNumber,
        ], $options);

        try {
            $response = $this->makeRequest('POST', '/v2/create-phone-call', $payload);

            if (! $response->successful()) {
                $error = $response->json();
                Log::error('Error creating Retell phone call', [
                    'error' => $error,
                    'payload' => $payload,
                ]);

                throw new \Exception(
                    $error['message'] ?? 'Error al crear la llamada telefónica',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exception creating Retell phone call', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw $e;
        }
    }

    /**
     * Crear llamada usando configuración de CampaignCallAgent
     *
     * @param  CampaignCallAgent  $callAgent  Agente configurado de la campaña
     * @param  string  $toNumber  Número destino en formato E.164
     * @param  array  $dynamicVariables  Variables dinámicas para el LLM
     * @param  array  $metadata  Metadatos adicionales
     * @return array Respuesta de la API
     *
     * @throws \Exception Si la llamada falla
     */
    public function createCallFromAgent(
        CampaignCallAgent $callAgent,
        string $toNumber,
        array $dynamicVariables = [],
        array $metadata = []
    ): array {
        $config = $callAgent->config ?? [];

        // Extraer número origen de la configuración
        $fromNumber = $config['from_number'] ?? null;

        if (! $fromNumber) {
            throw new \Exception('El agente no tiene configurado un número de origen (from_number)');
        }

        // Construir opciones para la llamada
        $options = [];

        // Agent ID si está configurado
        if (isset($config['agent_id'])) {
            $options['override_agent_id'] = $config['agent_id'];
        }

        // Agent version si está configurado
        if (isset($config['agent_version'])) {
            $options['override_agent_version'] = $config['agent_version'];
        }

        // Agent override completo si está configurado
        if (isset($config['agent_override'])) {
            $options['agent_override'] = $config['agent_override'];
        }

        // Variables dinámicas (incluir campaign_id y lead info)
        if (! empty($dynamicVariables)) {
            $options['retell_llm_dynamic_variables'] = $dynamicVariables;
        }

        // Metadatos (incluir campaign_id si está disponible)
        if (! empty($metadata)) {
            $options['metadata'] = $metadata;
        }

        return $this->createPhoneCall($fromNumber, $toNumber, $options);
    }

    /**
     * Obtener información de una llamada por su ID
     *
     * @param  string  $callId  ID de la llamada en Retell
     * @return array Información de la llamada
     *
     * @throws \Exception Si la consulta falla
     */
    public function getCall(string $callId): array
    {
        try {
            $response = $this->makeRequest('GET', "/v2/get-call/{$callId}");

            if (! $response->successful()) {
                $error = $response->json();
                throw new \Exception(
                    $error['message'] ?? 'Error al obtener información de la llamada',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error getting Retell call', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Listar todas las llamadas con filtros opcionales
     *
     * @param  array  $filters  Filtros: agent_id, from_number, to_number, start_timestamp, end_timestamp
     * @return array Lista de llamadas
     *
     * @throws \Exception Si la consulta falla
     */
    public function listCalls(array $filters = []): array
    {
        try {
            $response = $this->makeRequest('GET', '/v2/list-calls', $filters);

            if (! $response->successful()) {
                $error = $response->json();
                throw new \Exception(
                    $error['message'] ?? 'Error al listar llamadas',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error listing Retell calls', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener información de un agente por su ID
     *
     * @param  string  $agentId  ID del agente en Retell
     * @return array Información del agente
     *
     * @throws \Exception Si la consulta falla
     */
    public function getAgent(string $agentId): array
    {
        try {
            // El endpoint correcto según la documentación es /get-agent/{agent_id}
            $response = $this->makeRequest('GET', "/get-agent/{$agentId}");

            if (! $response->successful()) {
                $error = $response->json();
                throw new \Exception(
                    $error['message'] ?? 'Error al obtener información del agente',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error getting Retell agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Listar todos los agentes
     *
     * @return array Lista de agentes
     *
     * @throws \Exception Si la consulta falla
     */
    public function listAgents(): array
    {
        try {
            $response = $this->makeRequest('GET', '/list-agents');

            if (! $response->successful()) {
                $error = $response->json();
                $statusCode = $response->status();

                Log::error('Retell API error listing agents', [
                    'status' => $statusCode,
                    'error' => $error,
                    'response_body' => $response->body(),
                ]);

                throw new \Exception(
                    $error['message'] ?? $error['error'] ?? "Error al listar agentes (HTTP {$statusCode})",
                    $statusCode
                );
            }

            $data = $response->json();

            // Log para debugging
            Log::info('Retell agents listed successfully', [
                'count' => is_array($data) ? count($data) : (isset($data['agents']) ? count($data['agents']) : 0),
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('Error listing Retell agents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Crear un nuevo agente
     *
     * @param  array  $agentData  Datos del agente según documentación de Retell
     * @return array Información del agente creado
     *
     * @throws \Exception Si la creación falla
     */
    public function createAgent(array $agentData): array
    {
        try {
            // El endpoint correcto según la documentación es /create-agent
            $response = $this->makeRequest('POST', '/create-agent', $agentData);

            if (! $response->successful()) {
                $error = $response->json();
                Log::error('Error creating Retell agent', [
                    'error' => $error,
                    'agent_data' => $agentData,
                ]);

                throw new \Exception(
                    $error['message'] ?? 'Error al crear el agente',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exception creating Retell agent', [
                'error' => $e->getMessage(),
                'agent_data' => $agentData,
            ]);

            throw $e;
        }
    }

    /**
     * Actualizar un agente existente
     *
     * @param  string  $agentId  ID del agente a actualizar
     * @param  array  $agentData  Datos a actualizar
     * @return array Información del agente actualizado
     *
     * @throws \Exception Si la actualización falla
     */
    public function updateAgent(string $agentId, array $agentData): array
    {
        try {
            // El endpoint correcto según la documentación es /update-agent/{agent_id}
            $response = $this->makeRequest('PATCH', "/update-agent/{$agentId}", $agentData);

            if (! $response->successful()) {
                $error = $response->json();
                Log::error('Error updating Retell agent', [
                    'error' => $error,
                    'agent_id' => $agentId,
                    'agent_data' => $agentData,
                ]);

                throw new \Exception(
                    $error['message'] ?? 'Error al actualizar el agente',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exception updating Retell agent', [
                'error' => $e->getMessage(),
                'agent_id' => $agentId,
                'agent_data' => $agentData,
            ]);

            throw $e;
        }
    }

    /**
     * Eliminar un agente
     *
     * @param  string  $agentId  ID del agente a eliminar
     * @return bool True si se eliminó correctamente
     *
     * @throws \Exception Si la eliminación falla
     */
    public function deleteAgent(string $agentId): bool
    {
        try {
            // El endpoint correcto según la documentación es /delete-agent/{agent_id}
            $response = $this->makeRequest('DELETE', "/delete-agent/{$agentId}");

            if (! $response->successful()) {
                $error = $response->json();
                throw new \Exception(
                    $error['message'] ?? 'Error al eliminar el agente',
                    $response->status()
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting Retell agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Listar números telefónicos disponibles
     *
     * @return array Lista de números telefónicos
     *
     * @throws \Exception Si la consulta falla
     */
    public function listPhoneNumbers(): array
    {
        try {
            $response = $this->makeRequest('GET', '/v2/list-phone-numbers');

            if (! $response->successful()) {
                $error = $response->json();
                throw new \Exception(
                    $error['message'] ?? 'Error al listar números telefónicos',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error listing Retell phone numbers', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener información de un número telefónico
     *
     * @param  string  $phoneNumber  Número en formato E.164
     * @return array Información del número
     *
     * @throws \Exception Si la consulta falla
     */
    public function getPhoneNumber(string $phoneNumber): array
    {
        try {
            $response = $this->makeRequest('GET', "/v2/get-phone-number/{$phoneNumber}");

            if (! $response->successful()) {
                $error = $response->json();
                throw new \Exception(
                    $error['message'] ?? 'Error al obtener información del número',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error getting Retell phone number', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Realizar una petición HTTP a la API de Retell
     *
     * @param  string  $method  Método HTTP (GET, POST, PATCH, DELETE)
     * @param  string  $endpoint  Endpoint relativo (ej: /v2/create-phone-call)
     * @param  array  $data  Datos para el body (para POST/PATCH)
     * @return Response Respuesta HTTP
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): Response
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        Log::debug('Retell API Request', [
            'method' => $method,
            'url' => $url,
            'has_data' => !empty($data),
        ]);

        $request = Http::withHeaders($headers);

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PATCH' => $request->patch($url, $data),
            'DELETE' => $request->delete($url),
            default => throw new \InvalidArgumentException("Método HTTP no soportado: {$method}"),
        };

        // Log de respuesta para debugging
        Log::debug('Retell API Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body_preview' => substr($response->body(), 0, 500),
        ]);

        return $response;
    }
}
