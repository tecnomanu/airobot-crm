<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'uuid', 'exists:campaigns,id'],
            'agent_template_id' => ['required', 'uuid', 'exists:agent_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'intention_prompt' => ['required', 'string', 'min:20', 'max:5000'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['nullable', 'string'],
            'retell_config' => ['nullable', 'array'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'campaign_id.required' => 'La campaña es obligatoria',
            'campaign_id.exists' => 'La campaña seleccionada no existe',
            'agent_template_id.required' => 'El template de agente es obligatorio',
            'agent_template_id.exists' => 'El template seleccionado no existe',
            'name.required' => 'El nombre del agente es obligatorio',
            'intention_prompt.required' => 'El prompt de intención es obligatorio',
            'intention_prompt.min' => 'El prompt de intención debe tener al menos 20 caracteres',
            'intention_prompt.max' => 'El prompt de intención no puede exceder 5000 caracteres',
        ];
    }
}
