<?php

namespace App\Http\Requests\Campaign;

use App\Enums\CallAgentProvider;
use App\Enums\CampaignActionType;
use App\Enums\CampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos básicos de la campaña
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'uuid', 'exists:clients,id'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(CampaignStatus::class)],
            
            // Agente de llamadas
            'call_agent' => ['nullable', 'array'],
            'call_agent.name' => ['required_with:call_agent', 'string', 'max:255'],
            'call_agent.provider' => ['required_with:call_agent', Rule::enum(CallAgentProvider::class)],
            'call_agent.config' => ['nullable', 'array'],
            'call_agent.enabled' => ['nullable', 'boolean'],
            
            // Agente de WhatsApp
            'whatsapp_agent' => ['nullable', 'array'],
            'whatsapp_agent.name' => ['required_with:whatsapp_agent', 'string', 'max:255'],
            'whatsapp_agent.source_id' => ['nullable', 'integer', 'exists:sources,id'],
            'whatsapp_agent.config' => ['nullable', 'array'],
            'whatsapp_agent.enabled' => ['nullable', 'boolean'],
            
            // Opciones de la campaña
            'options' => ['nullable', 'array'],
            'options.*.option_key' => ['required', 'string', 'in:1,2,i,t'],
            'options.*.action' => ['required', Rule::enum(CampaignActionType::class)],
            'options.*.source_id' => ['nullable', 'integer', 'exists:sources,id'],
            'options.*.template_id' => ['nullable', 'string', 'uuid', 'exists:campaign_whatsapp_templates,id'],
            'options.*.message' => ['nullable', 'string'],
            'options.*.delay' => ['nullable', 'integer', 'min:0'],
            'options.*.enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es obligatorio',
            'client_id.required' => 'El cliente es obligatorio',
            'client_id.exists' => 'El cliente seleccionado no existe',
            'call_agent.name.required_with' => 'El nombre del agente de llamadas es obligatorio',
            'whatsapp_agent.name.required_with' => 'El nombre del agente de WhatsApp es obligatorio',
            'options.*.option_key.required' => 'La clave de opción es obligatoria',
            'options.*.option_key.in' => 'La clave de opción debe ser 1, 2, i o t',
        ];
    }

    /**
     * Prepara los datos para ser validados
     * Asegura que siempre existan las 4 opciones por defecto
     */
    protected function prepareForValidation(): void
    {
        // Si no se envían opciones, crear las 4 por defecto
        if (!$this->has('options') || empty($this->input('options'))) {
            $this->merge([
                'options' => [
                    ['option_key' => '1', 'action' => 'skip', 'enabled' => true, 'delay' => 5],
                    ['option_key' => '2', 'action' => 'skip', 'enabled' => true, 'delay' => 5],
                    ['option_key' => 'i', 'action' => 'skip', 'enabled' => true, 'delay' => 5],
                    ['option_key' => 't', 'action' => 'skip', 'enabled' => true, 'delay' => 5],
                ]
            ]);
        }
    }
}
