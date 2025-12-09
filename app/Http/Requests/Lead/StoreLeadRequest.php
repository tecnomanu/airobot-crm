<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadOptionSelected;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Helpers\PhoneHelper;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización manejada por middleware
    }

    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // campaign_id es UUID, mantenerlo como string
        if ($this->filled('campaign_id')) {
            $mergeData['campaign_id'] = trim($this->campaign_id);
        }

        if ($this->has('phone')) {
            // Obtener campaña para normalización de teléfono
            $campaign = null;
            if ($this->filled('campaign_id')) {
                $campaign = Campaign::find($this->campaign_id);
            }

            $normalizedPhone = PhoneHelper::normalizeForLead(
                $this->phone,
                $campaign
            );

            $mergeData['phone'] = $normalizedPhone;
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'option_selected' => ['nullable', Rule::enum(LeadOptionSelected::class)],
            'campaign_id' => ['required', 'string', 'uuid', 'exists:campaigns,id'],
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
