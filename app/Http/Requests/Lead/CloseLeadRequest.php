<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadCloseReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'close_reason' => ['required', 'string', Rule::in(LeadCloseReason::values())],
            'close_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'close_reason.required' => 'Debe seleccionar un motivo de cierre.',
            'close_reason.in' => 'El motivo de cierre seleccionado no es válido.',
        ];
    }

    public function getCloseReason(): LeadCloseReason
    {
        return LeadCloseReason::from($this->validated('close_reason'));
    }

    public function getCloseNotes(): ?string
    {
        return $this->validated('close_notes');
    }
}

