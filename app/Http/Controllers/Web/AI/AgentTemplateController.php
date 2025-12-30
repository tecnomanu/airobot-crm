<?php

namespace App\Http\Controllers\Web\AI;

use App\Enums\AgentTemplateType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\StoreAgentTemplateRequest;
use App\Http\Requests\AI\UpdateAgentTemplateRequest;
use App\Models\AI\AgentTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgentTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = AgentTemplate::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $templates = $query->latest()->paginate(15);

        return Inertia::render('AI/AgentTemplates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['type', 'is_active', 'search']),
            'templateTypes' => AgentTemplateType::cases(),
        ]);
    }

    public function create()
    {
        return Inertia::render('AI/AgentTemplates/Create', [
            'templateTypes' => AgentTemplateType::cases(),
        ]);
    }

    public function store(StoreAgentTemplateRequest $request)
    {
        $template = AgentTemplate::create($request->validated());

        return redirect()
            ->route('admin.agent-templates.show', $template)
            ->with('success', 'Template de agente creado exitosamente');
    }

    public function show(AgentTemplate $agentTemplate)
    {
        $agentTemplate->load(['campaignAgents' => function ($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('AI/AgentTemplates/Show', [
            'template' => $agentTemplate,
        ]);
    }

    public function edit(AgentTemplate $agentTemplate)
    {
        return Inertia::render('AI/AgentTemplates/Edit', [
            'template' => $agentTemplate,
            'templateTypes' => AgentTemplateType::cases(),
        ]);
    }

    public function update(UpdateAgentTemplateRequest $request, AgentTemplate $agentTemplate)
    {
        $agentTemplate->update($request->validated());

        return redirect()
            ->route('admin.agent-templates.show', $agentTemplate)
            ->with('success', 'Template actualizado exitosamente');
    }

    public function destroy(AgentTemplate $agentTemplate)
    {
        if ($agentTemplate->hasActiveCampaignAgents()) {
            return back()->with('error', 'No se puede eliminar un template que tiene agentes activos');
        }

        $agentTemplate->delete();

        return redirect()
            ->route('admin.agent-templates.index')
            ->with('success', 'Template eliminado exitosamente');
    }

    public function duplicate(AgentTemplate $agentTemplate)
    {
        $newTemplate = $agentTemplate->replicate();
        $newTemplate->name = $agentTemplate->name . ' (Copia)';
        $newTemplate->is_active = false;
        $newTemplate->save();

        return redirect()
            ->route('admin.agent-templates.edit', $newTemplate)
            ->with('success', 'Template duplicado exitosamente');
    }
}
