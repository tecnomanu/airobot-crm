<?php

namespace App\Http\Controllers\Web\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\StoreCampaignAgentRequest;
use App\Http\Requests\AI\UpdateCampaignAgentRequest;
use App\Models\AI\AgentTemplate;
use App\Models\AI\CampaignAgent;
use App\Models\Campaign\Campaign;
use App\Services\AI\CampaignAgentService;
use App\Services\AI\PromptComposerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CampaignAgentController extends Controller
{
    protected CampaignAgentService $agentService;

    public function __construct(CampaignAgentService $agentService)
    {
        $this->agentService = $agentService;
    }

    public function index(Request $request)
    {
        $query = CampaignAgent::with(['campaign', 'template']);

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('is_synced')) {
            $query->where('is_synced', $request->boolean('is_synced'));
        }

        $agents = $query->latest()->paginate(15);

        return Inertia::render('AI/CampaignAgents/Index', [
            'agents' => $agents,
            'filters' => $request->only(['campaign_id', 'is_synced']),
        ]);
    }

    public function create(Request $request)
    {
        $campaigns = Campaign::select('id', 'name')->orderBy('name')->get();
        $templates = AgentTemplate::active()->orderBy('name')->get();

        $preselectedCampaign = null;
        if ($request->filled('campaign_id')) {
            $preselectedCampaign = Campaign::find($request->campaign_id);
        }

        return Inertia::render('AI/CampaignAgents/Create', [
            'campaigns' => $campaigns,
            'templates' => $templates,
            'preselectedCampaign' => $preselectedCampaign,
        ]);
    }

    public function store(StoreCampaignAgentRequest $request)
    {
        try {
            $autoSync = $request->boolean('auto_sync', false);

            $agent = $this->agentService->createAgent(
                $request->validated(),
                $autoSync
            );

            return redirect()
                ->route('admin.campaign-agents.show', $agent)
                ->with('success', 'Agente creado y prompts generados exitosamente');
        } catch (\Exception $e) {
            Log::error('Error creating campaign agent', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al crear el agente: ' . $e->getMessage());
        }
    }

    public function show(CampaignAgent $campaignAgent)
    {
        $campaignAgent->load(['campaign', 'template']);

        return Inertia::render('AI/CampaignAgents/Show', [
            'agent' => $campaignAgent,
        ]);
    }

    public function edit(CampaignAgent $campaignAgent)
    {
        $campaignAgent->load(['campaign', 'template']);
        $templates = AgentTemplate::active()->orderBy('name')->get();

        return Inertia::render('AI/CampaignAgents/Edit', [
            'agent' => $campaignAgent,
            'templates' => $templates,
        ]);
    }

    public function update(UpdateCampaignAgentRequest $request, CampaignAgent $campaignAgent)
    {
        try {
            $regenerate = $request->boolean('regenerate', false);

            $agent = $this->agentService->updateAgent(
                $campaignAgent,
                $request->validated(),
                $regenerate
            );

            return redirect()
                ->route('admin.campaign-agents.show', $agent)
                ->with('success', 'Agente actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error updating campaign agent', [
                'agent_id' => $campaignAgent->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar el agente: ' . $e->getMessage());
        }
    }

    public function destroy(CampaignAgent $campaignAgent)
    {
        try {
            $deleteFromRetell = request()->boolean('delete_from_retell', true);

            $this->agentService->deleteAgent($campaignAgent, $deleteFromRetell);

            return redirect()
                ->route('admin.campaign-agents.index')
                ->with('success', 'Agente eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el agente: ' . $e->getMessage());
        }
    }

    /**
     * Regenerate prompts for an agent
     */
    public function generate(CampaignAgent $campaignAgent)
    {
        try {
            $this->agentService->generatePrompts($campaignAgent);

            return back()->with('success', 'Prompts regenerados exitosamente');
        } catch (\Exception $e) {
            Log::error('Error regenerating prompts', [
                'agent_id' => $campaignAgent->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al regenerar prompts: ' . $e->getMessage());
        }
    }

    /**
     * Sync agent with Retell
     */
    public function sync(CampaignAgent $campaignAgent)
    {
        try {
            $forceUpdate = request()->boolean('force_update', false);

            $this->agentService->syncWithRetell($campaignAgent, $forceUpdate);

            return back()->with('success', 'Agente sincronizado con Retell exitosamente');
        } catch (\Exception $e) {
            Log::error('Error syncing with Retell', [
                'agent_id' => $campaignAgent->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al sincronizar con Retell: ' . $e->getMessage());
        }
    }

    /**
     * Preview prompt before saving
     */
    public function preview(Request $request)
    {
        $request->validate([
            'agent_template_id' => ['required', 'uuid', 'exists:agent_templates,id'],
            'intention_prompt' => ['required', 'string', 'min:20'],
            'variables' => ['nullable', 'array'],
        ]);

        try {
            $template = AgentTemplate::findOrFail($request->agent_template_id);

            // Create temporary agent (not saved)
            $tempAgent = new CampaignAgent([
                'intention_prompt' => $request->intention_prompt,
                'variables' => $request->variables ?? [],
            ]);
            $tempAgent->setRelation('template', $template);

            // Generate flow section
            $promptComposer = app(PromptComposerService::class);
            $flowSection = $promptComposer->generateFlowSection(
                $template,
                $request->intention_prompt,
                $request->variables ?? []
            );

            $tempAgent->flow_section = $flowSection;

            // Compose final prompt
            $finalPrompt = $promptComposer->composeFinalPrompt($tempAgent);

            return response()->json([
                'flow_section' => $flowSection,
                'final_prompt' => $finalPrompt,
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating preview', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error al generar preview: ' . $e->getMessage(),
            ], 500);
        }
    }
}
