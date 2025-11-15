<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadOptionSelected;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['sometimes', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'option_selected' => ['nullable', Rule::enum(LeadOptionSelected::class)],
            'campaign_id' => ['sometimes', 'integer', 'exists:campaigns,id'],
            'status' => ['sometimes', Rule::enum(LeadStatus::class)],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'intention' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
