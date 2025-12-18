<?php

namespace App\Http\Requests\Campaign;

use App\Enums\CallAgentProvider;
use App\Enums\CampaignActionType;
use App\Enums\CampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos básicos de la campaña
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-_]+$/',
                Rule::unique('campaigns', 'slug')->ignore($this->route('campaign')),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(CampaignStatus::class)],
            'auto_process_enabled' => ['nullable', 'boolean'],

            // Google Sheets Integration
            'google_integration_id' => ['nullable', 'string', 'exists:google_integrations,id'],
            'google_spreadsheet_id' => ['nullable', 'string'],
            'google_sheet_name' => ['nullable', 'string'],

            // Webhooks de intención
            'intention_interested_webhook_id' => ['nullable', 'integer', 'exists:sources,id'],
            'intention_not_interested_webhook_id' => ['nullable', 'integer', 'exists:sources,id'],
            'send_intention_interested_webhook' => ['nullable', 'boolean'],
            'send_intention_not_interested_webhook' => ['nullable', 'boolean'],

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
            'name.string' => 'El nombre debe ser un texto válido',
            'call_agent.name.required_with' => 'El nombre del agente de llamadas es obligatorio',
            'whatsapp_agent.name.required_with' => 'El nombre del agente de WhatsApp es obligatorio',
            'options.*.option_key.required' => 'La clave de opción es obligatoria',
            'options.*.option_key.in' => 'La clave de opción debe ser 1, 2, i o t',
        ];
    }
}
