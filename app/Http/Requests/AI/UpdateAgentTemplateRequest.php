<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'style_section' => ['sometimes', 'required', 'string', 'min:50'],
            'behavior_section' => ['sometimes', 'required', 'string', 'min:50'],
            'data_section_template' => ['nullable', 'string'],
            'available_variables' => ['nullable', 'array'],
            'available_variables.*' => ['string', 'alpha_dash'],
            'retell_config_template' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
