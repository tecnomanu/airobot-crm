<?php

namespace App\Http\Controllers\Web\CallAgent;

use App\Http\Controllers\Controller;
use App\Http\Resources\CallAgent\CallAgentResource;
use App\Services\CallProvider\CallAgentConfigService;
use App\Services\CallProvider\RetellService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CallAgentController extends Controller
{
    public function __construct(
        private RetellService $retellService,
        private CallAgentConfigService $configService
    ) {}

    /**
     * Listar todos los agentes de Retell
     */
    public function index(Request $request): Response
    {
        try {
            $response = $this->retellService->listAgents();

            // Según la documentación de Retell, la respuesta es un array directo de agentes
            // Ejemplo: [{agent_id: "...", agent_name: "...", version: 0, ...}, ...]
            $allAgents = is_array($response) ? $response : [];

            // Agrupar agentes por agent_id y mantener solo la versión más reciente de cada uno
            // Retell devuelve múltiples entradas para el mismo agente con diferentes versiones
            $agentsGrouped = [];
            foreach ($allAgents as $agent) {
                $agentId = $agent['agent_id'] ?? null;
                if (!$agentId) {
                    continue;
                }

                // Si no existe o la versión actual es mayor, actualizar
                if (
                    !isset($agentsGrouped[$agentId]) ||
                    ($agent['version'] ?? 0) > ($agentsGrouped[$agentId]['version'] ?? 0)
                ) {
                    $agentsGrouped[$agentId] = $agent;
                }
            }

            // Convertir a array indexado numéricamente y formatear
            $agents = array_values(array_map(fn($agent) => (new CallAgentResource($agent))->resolve(), $agentsGrouped));

            return Inertia::render('CallAgents/Index', [
                'agents' => $agents,
                'totalVersions' => count($allAgents),
                'uniqueAgents' => count($agents),
            ]);
        } catch (\RuntimeException $e) {
            // Error de configuración (API key faltante)
            return Inertia::render('CallAgents/Index', [
                'agents' => [],
                'error' => $e->getMessage(),
                'errorType' => 'configuration',
            ]);
        } catch (\Exception $e) {
            // Otros errores (conexión, API, etc.)
            Log::error('Error in CallAgentController@index', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('CallAgents/Index', [
                'agents' => [],
                'error' => 'Error al conectar con Retell AI: ' . $e->getMessage(),
                'errorType' => 'api',
            ]);
        }
    }

    /**
     * Mostrar formulario para crear nuevo agente
     */
    public function create(): Response
    {
        try {
            $defaultConfig = $this->configService->getDefaultConfig();

            return Inertia::render('CallAgents/Show', [
                'agent' => null,
                'phoneNumbers' => $this->getPhoneNumbers(),
                'defaultConfig' => $defaultConfig?->config ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return Inertia::render('CallAgents/Show', [
                'agent' => null,
                'phoneNumbers' => [],
                'defaultConfig' => null,
                'error' => $e->getMessage(),
                'errorType' => 'configuration',
            ]);
        } catch (\Exception $e) {
            return Inertia::render('CallAgents/Show', [
                'agent' => null,
                'phoneNumbers' => [],
                'defaultConfig' => null,
                'error' => 'Error al conectar con Retell AI: ' . $e->getMessage(),
                'errorType' => 'api',
            ]);
        }
    }

    /**
     * Mostrar formulario para editar agente existente
     */
    public function show(string $id): Response
    {
        try {
            $agent = $this->retellService->getAgent($id);
            $defaultConfig = $this->configService->getDefaultConfig();

            return Inertia::render('CallAgents/Show', [
                'agent' => (new CallAgentResource($agent))->resolve(),
                'phoneNumbers' => $this->getPhoneNumbers(),
                'defaultConfig' => $defaultConfig?->config ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return Inertia::render('CallAgents/Show', [
                'agent' => null,
                'phoneNumbers' => [],
                'defaultConfig' => null,
                'error' => $e->getMessage(),
                'errorType' => 'configuration',
            ]);
        } catch (\Exception $e) {
            return Inertia::render('CallAgents/Show', [
                'agent' => null,
                'phoneNumbers' => [],
                'defaultConfig' => null,
                'error' => 'Error al obtener agente: ' . $e->getMessage(),
                'errorType' => 'api',
            ]);
        }
    }

    /**
     * Crear nuevo agente en Retell
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'agent_name' => 'required|string|max:255',
            'voice_id' => 'required|string',
            'language' => 'required|string|max:10',
            'prompt' => 'nullable|string',
            'first_message' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'llm_model' => 'nullable|string',
            'llm_temperature' => 'nullable|numeric|min:0|max:1',
            'voice_speed' => 'nullable|numeric|min:0.5|max:2.0',
            'voice_temperature' => 'nullable|numeric|min:0|max:1',
            'stt_mode' => 'nullable|string|in:fast,accurate',
            'vocab_specialization' => 'nullable|string|in:general,medical',
            'denoising_mode' => 'nullable|string|in:noise-cancellation,noise-and-background-speech-cancellation',
            'end_call_after_silence_ms' => 'nullable|integer|min:10000',
            'max_call_duration_ms' => 'nullable|integer|min:60000|max:7200000',
            'ring_duration_ms' => 'nullable|integer|min:5000|max:90000',
            'webhook_timeout_ms' => 'nullable|integer|min:1000|max:60000',
        ]);

        try {
            $agentData = $this->buildAgentData($request->all());
            $agent = $this->retellService->createAgent($agentData);

            return redirect()->route('call-agents.index')
                ->with('success', 'Agente creado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear agente: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Actualizar agente existente
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'agent_name' => 'required|string|max:255',
            'voice_id' => 'required|string',
            'language' => 'required|string|max:10',
            'prompt' => 'nullable|string',
            'first_message' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'llm_model' => 'nullable|string',
            'llm_temperature' => 'nullable|numeric|min:0|max:1',
            'voice_speed' => 'nullable|numeric|min:0.5|max:2.0',
            'voice_temperature' => 'nullable|numeric|min:0|max:1',
            'stt_mode' => 'nullable|string|in:fast,accurate',
            'vocab_specialization' => 'nullable|string|in:general,medical',
            'denoising_mode' => 'nullable|string|in:noise-cancellation,noise-and-background-speech-cancellation',
            'end_call_after_silence_ms' => 'nullable|integer|min:10000',
            'max_call_duration_ms' => 'nullable|integer|min:60000|max:7200000',
            'ring_duration_ms' => 'nullable|integer|min:5000|max:90000',
            'webhook_timeout_ms' => 'nullable|integer|min:1000|max:60000',
        ]);

        try {
            $agentData = $this->buildAgentData($request->all());
            $this->retellService->updateAgent($id, $agentData);

            return redirect()->back()
                ->with('success', 'Agente actualizado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar agente: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Eliminar agente
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $this->retellService->deleteAgent($id);

            return redirect()->route('call-agents.index')
                ->with('success', 'Agente eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar agente: ' . $e->getMessage());
        }
    }

    /**
     * Obtener números telefónicos disponibles
     */
    private function getPhoneNumbers(): array
    {
        try {
            $response = $this->retellService->listPhoneNumbers();
            return $response['phone_numbers'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Construir estructura de datos del agente según API de Retell
     * Usa configuración base + overrides específicos
     */
    private function buildAgentData(array $requestData): array
    {
        // Usar el servicio para construir datos con configuración base
        $agentData = $this->configService->buildAgentDataForRetell([
            'agent_name' => $requestData['agent_name'],
            'voice_id' => $requestData['voice_id'],
            'language' => $requestData['language'],
            'first_message' => $requestData['first_message'] ?? null,
            'voice_speed' => $requestData['voice_speed'] ?? null,
            'voice_temperature' => $requestData['voice_temperature'] ?? null,
            'llm_model' => $requestData['llm_model'] ?? null,
            'llm_temperature' => $requestData['llm_temperature'] ?? null,
            'webhook_url' => $requestData['webhook_url'] ?? null,
            'stt_mode' => $requestData['stt_mode'] ?? null,
            'vocab_specialization' => $requestData['vocab_specialization'] ?? null,
            'denoising_mode' => $requestData['denoising_mode'] ?? null,
            'end_call_after_silence_ms' => $requestData['end_call_after_silence_ms'] ?? null,
            'max_call_duration_ms' => $requestData['max_call_duration_ms'] ?? null,
            'ring_duration_ms' => $requestData['ring_duration_ms'] ?? null,
            'webhook_timeout_ms' => $requestData['webhook_timeout_ms'] ?? null,
        ]);

        // Agregar prompt si está presente (para Single-Prompt Agent)
        if (!empty($requestData['prompt'])) {
            // El prompt puede ir en diferentes lugares según el tipo de agente
            // Para Single-Prompt Agent, se puede pasar como parte del response_engine
            // o como campo separado según la documentación
            $agentData['prompt'] = $requestData['prompt'];
        }

        return $agentData;
    }
}
