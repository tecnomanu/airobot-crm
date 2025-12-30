<?php

namespace App\Services\AI;

use App\Models\AI\AgentTemplate;
use App\Models\AI\CampaignAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * High-level service for managing campaign agents
 *
 * Orchestrates prompt composition, generation, and synchronization
 */
class CampaignAgentService
{
    protected PromptComposerService $promptComposer;
    protected RetellAgentSyncService $retellSync;

    public function __construct(
        PromptComposerService $promptComposer,
        RetellAgentSyncService $retellSync
    ) {
        $this->promptComposer = $promptComposer;
        $this->retellSync = $retellSync;
    }

    /**
     * Create and generate a new campaign agent
     *
     * @param array $data Agent data
     * @param bool $autoSync Auto-sync with Retell after creation
     * @return CampaignAgent
     */
    public function createAgent(array $data, bool $autoSync = false): CampaignAgent
    {
        return DB::transaction(function () use ($data, $autoSync) {
            // Create agent
            $agent = CampaignAgent::create($data);

            // Generate prompts
            $this->generatePrompts($agent);

            // Auto-sync if requested
            if ($autoSync && $agent->hasFinalPrompt()) {
                try {
                    $this->retellSync->syncAgent($agent);
                } catch (\Exception $e) {
                    Log::warning('Auto-sync failed during agent creation', [
                        'agent_id' => $agent->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the creation, just log the warning
                }
            }

            return $agent->fresh();
        });
    }

    /**
     * Update an existing agent
     *
     * @param CampaignAgent $agent
     * @param array $data
     * @param bool $regenerate Force regeneration of prompts
     * @return CampaignAgent
     */
    public function updateAgent(CampaignAgent $agent, array $data, bool $regenerate = false): CampaignAgent
    {
        return DB::transaction(function () use ($agent, $data, $regenerate) {
            // Check if intention or variables changed
            $intentionChanged = isset($data['intention_prompt']) && $data['intention_prompt'] !== $agent->intention_prompt;
            $variablesChanged = isset($data['variables']) && $data['variables'] !== $agent->variables;

            // Update agent
            $agent->update($data);

            // Regenerate if needed or requested
            if ($regenerate || $intentionChanged || $variablesChanged) {
                $this->generatePrompts($agent);
            }

            return $agent->fresh();
        });
    }

    /**
     * Generate flow section and compose final prompt
     *
     * @param CampaignAgent $agent
     * @return CampaignAgent
     */
    public function generatePrompts(CampaignAgent $agent): CampaignAgent
    {
        $template = $agent->template;

        if (!$template) {
            throw new \Exception('Agent template not loaded');
        }

        try {
            Log::info('Generating prompts for campaign agent', [
                'agent_id' => $agent->id,
                'template_type' => $template->type->value,
            ]);

            // 1. Generate flow section using LLM
            $flowSection = $this->promptComposer->generateFlowSection(
                $template,
                $agent->intention_prompt,
                $agent->getVariables()
            );

            $agent->flow_section = $flowSection;
            $agent->save();

            // 2. Compose final prompt
            $finalPrompt = $this->promptComposer->composeFinalPrompt($agent);

            // 3. Validate
            $validation = $this->promptComposer->validatePrompt($finalPrompt);

            if (!$validation['valid']) {
                throw new \Exception('Prompt validation failed: ' . implode(', ', $validation['errors']));
            }

            $agent->final_prompt = $finalPrompt;
            $agent->is_synced = false; // Mark as needing sync
            $agent->save();

            Log::info('Prompts generated successfully', [
                'agent_id' => $agent->id,
                'final_prompt_length' => strlen($finalPrompt),
            ]);

            return $agent;
        } catch (\Exception $e) {
            Log::error('Error generating prompts', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync agent with Retell
     *
     * @param CampaignAgent $agent
     * @param bool $forceUpdate
     * @return array Retell response
     */
    public function syncWithRetell(CampaignAgent $agent, bool $forceUpdate = false): array
    {
        if (!$agent->hasFinalPrompt()) {
            throw new \Exception('El agente no tiene un prompt final. Genera el prompt primero.');
        }

        return $this->retellSync->syncAgent($agent, $forceUpdate);
    }

    /**
     * Delete agent and cleanup Retell
     *
     * @param CampaignAgent $agent
     * @param bool $deleteFromRetell
     * @return bool
     */
    public function deleteAgent(CampaignAgent $agent, bool $deleteFromRetell = true): bool
    {
        return DB::transaction(function () use ($agent, $deleteFromRetell) {
            // Delete from Retell if requested
            if ($deleteFromRetell && $agent->hasRetellAgent()) {
                try {
                    $this->retellSync->deleteRetellAgent($agent);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete agent from Retell', [
                        'agent_id' => $agent->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with local deletion
                }
            }

            // Delete locally
            return $agent->delete();
        });
    }
}
