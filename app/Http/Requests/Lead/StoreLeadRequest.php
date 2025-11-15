<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadOptionSelected;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización manejada por middleware
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'option_selected' => ['nullable', Rule::enum(LeadOptionSelected::class)],
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'intention' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'El teléfono es obligatorio',
            'campaign_id.required' => 'La campaña es obligatoria',
            'campaign_id.exists' => 'La campaña seleccionada no existe',
        ];
    }
}
