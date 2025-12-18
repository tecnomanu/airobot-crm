<?php

namespace App\Http\Requests\Webhook;

use App\Enums\CallStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para recibir registros de llamadas desde proveedores externos (Vapi, Retell, etc)
 */
class CallWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Validar token/secret en header
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'call_id_external' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::enum(CallStatus::class)],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'campaign_id' => ['nullable', 'string', 'exists:campaigns,id'],
            'client_id' => ['nullable', 'string', 'exists:clients,id'],
            'lead_id' => ['nullable', 'string', 'exists:leads,id'],
            'notes' => ['nullable', 'string'],
            'recording_url' => ['nullable', 'url', 'max:500'],
            'transcript' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone is required',
            'status.required' => 'Call status is required',
        ];
    }
}
