<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', Rule::in(LeadStage::values())],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'stage.required' => 'Debe seleccionar un stage.',
            'stage.in' => 'El stage seleccionado no es válido.',
        ];
    }

    public function getStage(): LeadStage
    {
        return LeadStage::from($this->validated('stage'));
    }

    public function getReason(): ?string
    {
        return $this->validated('reason');
    }
}

