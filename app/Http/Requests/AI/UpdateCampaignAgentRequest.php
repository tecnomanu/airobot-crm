<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'intention_prompt' => ['sometimes', 'required', 'string', 'min:20', 'max:5000'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['nullable', 'string'],
            'flow_section' => ['nullable', 'string'], // Allow manual editing
            'final_prompt' => ['nullable', 'string'], // Allow manual editing
            'retell_config' => ['nullable', 'array'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del agente es obligatorio',
            'intention_prompt.min' => 'El prompt de intención debe tener al menos 20 caracteres',
        ];
    }
}
