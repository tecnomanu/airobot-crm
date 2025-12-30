<?php

namespace App\Services\AI;

use App\Models\AI\CampaignAgent;
use App\Services\CallProvider\RetellService;
use Illuminate\Support\Facades\Log;

/**
 * Service for synchronizing campaign agents with Retell AI
 *
 * Handles creating, updating, and deleting agents in Retell,
 * including webhook configuration and tool definitions.
 */
class RetellAgentSyncService
{
    protected RetellService $retellService;
    protected PromptComposerService $promptComposer;

    public function __construct(
        RetellService $retellService,
        PromptComposerService $promptComposer
    ) {
        $this->retellService = $retellService;
        $this->promptComposer = $promptComposer;
    }

    /**
     * Sync agent with Retell (create or update)
     *
     * @param CampaignAgent $agent The campaign agent to sync
     * @param bool $forceUpdate Force update even if already synced
     * @return array Retell API response
     *
     * @throws \Exception If sync fails
     */
    public function syncAgent(CampaignAgent $agent, bool $forceUpdate = false): array
    {
        try {
            // Verificar que tenga prompt final
            if (empty($agent->final_prompt)) {
                throw new \Exception('El agente no tiene un prompt final generado');
            }

            // Decidir si crear o actualizar
            if ($agent->hasRetellAgent() && !$forceUpdate) {
                $result = $this->updateRetellAgent($agent);
            } else {
                $result = $this->createRetellAgent($agent);
            }

            // Actualizar estado de sincronización
            $agent->is_synced = true;
            $agent->last_synced_at = now();
            $agent->save();

            Log::info('Agent synced with Retell successfully', [
                'agent_id' => $agent->id,
                'retell_agent_id' => $agent->retell_agent_id,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error syncing agent with Retell', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a new agent in Retell
     *
     * @param CampaignAgent $agent
     * @return array Retell API response
     */
    public function createRetellAgent(CampaignAgent $agent): array
    {
        $payload = $this->buildRetellPayload($agent);

        Log::info('Creating new Retell agent', [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
        ]);

        $response = $this->retellService->createAgent($payload);

        // Guardar el ID de Retell y la configuración
        $agent->retell_agent_id = $response['agent_id'] ?? null;
        $agent->retell_config = $payload;
        $agent->save();

        return $response;
    }

    /**
     * Update an existing agent in Retell
     *
     * @param CampaignAgent $agent
     * @return array Retell API response
     */
    public function updateRetellAgent(CampaignAgent $agent): array
    {
        if (!$agent->hasRetellAgent()) {
            throw new \Exception('El agente no tiene un ID de Retell para actualizar');
        }

        $payload = $this->buildRetellPayload($agent);

        Log::info('Updating Retell agent', [
            'agent_id' => $agent->id,
            'retell_agent_id' => $agent->retell_agent_id,
        ]);

        $response = $this->retellService->updateAgent($agent->retell_agent_id, $payload);

        // Actualizar configuración guardada
        $agent->retell_config = $payload;
        $agent->save();

        return $response;
    }

    /**
     * Delete agent from Retell
     *
     * @param CampaignAgent $agent
     * @return bool
     */
    public function deleteRetellAgent(CampaignAgent $agent): bool
    {
        if (!$agent->hasRetellAgent()) {
            Log::warning('Attempted to delete non-existent Retell agent', [
                'agent_id' => $agent->id,
            ]);
            return true; // No hay nada que eliminar
        }

        try {
            $this->retellService->deleteAgent($agent->retell_agent_id);

            // Limpiar datos de Retell
            $agent->retell_agent_id = null;
            $agent->is_synced = false;
            $agent->last_synced_at = null;
            $agent->save();

            Log::info('Retell agent deleted successfully', [
                'agent_id' => $agent->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting Retell agent', [
                'agent_id' => $agent->id,
                'retell_agent_id' => $agent->retell_agent_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build the payload for Retell API
     *
     * @param CampaignAgent $agent
     * @return array
     */
    public function buildRetellPayload(CampaignAgent $agent): array
    {
        $template = $agent->template;
        $baseConfig = $template?->getRetellConfigTemplate() ?? [];

        // Merge agent-specific config with template config
        $agentConfig = $agent->getRetellConfig();
        $config = array_merge($baseConfig, $agentConfig);

        // Base payload
        $payload = [
            'agent_name' => $agent->name,
            'general_prompt' => $agent->final_prompt,
            'response_engine' => [
                'type' => 'retell-llm',
                'llm_id' => $config['llm_id'] ?? 'gpt-4o-mini',
            ],
        ];

        // Voice configuration
        if (isset($config['voice_id'])) {
            $payload['voice_id'] = $config['voice_id'];
        }

        // Voice temperature/speed
        if (isset($config['voice_temperature'])) {
            $payload['voice_temperature'] = $config['voice_temperature'];
        }

        if (isset($config['voice_speed'])) {
            $payload['voice_speed'] = $config['voice_speed'];
        }

        // Language
        if (isset($config['language'])) {
            $payload['language'] = $config['language'];
        } else {
            $payload['language'] = 'es-ES'; // Default español
        }

        // Webhooks
        $payload = $this->addWebhooksToPayload($payload, $agent);

        // Tools (functions)
        $payload = $this->addToolsToPayload($payload, $agent);

        // Additional Retell-specific configurations
        if (isset($config['ambient_sound'])) {
            $payload['ambient_sound'] = $config['ambient_sound'];
        }

        if (isset($config['boosted_keywords'])) {
            $payload['boosted_keywords'] = $config['boosted_keywords'];
        }

        if (isset($config['interruption_sensitivity'])) {
            $payload['interruption_sensitivity'] = $config['interruption_sensitivity'];
        }

        return $payload;
    }

    /**
     * Add webhooks to payload
     */
    private function addWebhooksToPayload(array $payload, CampaignAgent $agent): array
    {
        $campaign = $agent->campaign;
        $baseUrl = config('app.url');

        // Webhook de lead interesado (si está configurado en la campaña)
        if ($campaign && $campaign->shouldSendInterestedWebhook()) {
            $interestedAction = $campaign->getInterestedAction();
            if ($interestedAction && $interestedAction->webhook_url) {
                $payload['custom_tool_webhooks'] = $payload['custom_tool_webhooks'] ?? [];
                $payload['custom_tool_webhooks'][] = [
                    'name' => 'webhook_lead_interested',
                    'url' => $interestedAction->webhook_url,
                    'description' => 'Webhook to call when lead shows interest',
                ];
            }
        }

        // Webhook interno para tracking (opcional)
        $payload['webhook_url'] = "{$baseUrl}/api/retell/call-status";

        return $payload;
    }

    /**
     * Add tools/functions to payload
     */
    private function addToolsToPayload(array $payload, CampaignAgent $agent): array
    {
        $config = $agent->getRetellConfig();
        $tools = $config['tools'] ?? [];

        // Tool por defecto: end_call
        $defaultTools = [
            [
                'type' => 'end_call',
                'name' => 'end_call',
                'description' => 'Finalizar la llamada inmediatamente',
            ],
        ];

        // Tool para webhook de lead interesado
        if ($agent->campaign && $agent->campaign->shouldSendInterestedWebhook()) {
            $defaultTools[] = [
                'type' => 'webhook',
                'name' => 'webhook_lead_interested',
                'description' => 'Marcar lead como interesado y registrar disponibilidad',
                'parameters' => [
                    [
                        'name' => 'lead_interested',
                        'type' => 'string',
                        'description' => 'Frase literal del cliente con su disponibilidad',
                        'required' => true,
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string',
                        'description' => 'Nombre del lead',
                        'required' => false,
                    ],
                    [
                        'name' => 'phone',
                        'type' => 'string',
                        'description' => 'Teléfono del lead',
                        'required' => false,
                    ],
                    [
                        'name' => 'notes',
                        'type' => 'string',
                        'description' => 'Notas adicionales',
                        'required' => false,
                    ],
                ],
            ];
        }

        // Merge con tools personalizados
        $allTools = array_merge($defaultTools, $tools);

        if (!empty($allTools)) {
            $payload['tools'] = $allTools;
        }

        return $payload;
    }
}
