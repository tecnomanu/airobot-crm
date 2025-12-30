<?php

namespace App\Http\Requests\AI;

use App\Enums\AgentTemplateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AgentTemplateType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'style_section' => ['required', 'string', 'min:50'],
            'behavior_section' => ['required', 'string', 'min:50'],
            'data_section_template' => ['nullable', 'string'],
            'available_variables' => ['nullable', 'array'],
            'available_variables.*' => ['string', 'alpha_dash'],
            'retell_config_template' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del template es obligatorio',
            'type.required' => 'El tipo de agente es obligatorio',
            'style_section.required' => 'La sección de estilo es obligatoria',
            'style_section.min' => 'La sección de estilo debe tener al menos 50 caracteres',
            'behavior_section.required' => 'La sección de comportamiento es obligatoria',
            'behavior_section.min' => 'La sección de comportamiento debe tener al menos 50 caracteres',
        ];
    }
}
