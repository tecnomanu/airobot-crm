<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadOptionSelected;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Helpers\PhoneHelper;
use App\Models\Campaign\Campaign;
use App\Models\Lead\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            // Obtener lead actual para contexto
            $lead = $this->route('lead');

            // Obtener campaña (nueva o existente)
            $campaign = null;
            if ($this->has('campaign_id')) {
                $campaign = Campaign::find($this->campaign_id);
            } elseif ($lead && $lead->campaign) {
                $campaign = $lead->campaign;
            }

            $normalizedPhone = PhoneHelper::normalizeForLead(
                $this->phone,
                $campaign,
                $lead
            );

            $this->merge([
                'phone' => $normalizedPhone,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => ['sometimes', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'option_selected' => ['nullable', Rule::enum(LeadOptionSelected::class)],
            'campaign_id' => ['sometimes', 'integer', 'exists:campaigns,id'],
            'status' => ['sometimes', Rule::enum(LeadStatus::class)],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'intention' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
