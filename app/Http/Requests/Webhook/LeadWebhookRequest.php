<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para recibir leads desde webhooks externos
 * Puede incluir campaign_pattern para auto-asignar o campaign_id directo
 */
class LeadWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Validar token/secret en header
        return true;
    }

    public function rules(): array
    {
        return [
            // Campos requeridos
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],

            // Campos opcionales de información
            'city' => ['nullable', 'string', 'max:255'],
            'option_selected' => ['nullable', 'string', 'in:1,2,i,t'], // Acepta: 1, 2, i, t

            // Asignación de campaña (ID directo o slug)
            'campaign_id' => ['nullable', 'string', 'exists:campaigns,id'],
            'campaign' => ['nullable', 'string', 'max:255'], // Slug de la campaña
            'campaign_slug' => ['nullable', 'string', 'max:255'], // Alias de campaign
            'slug' => ['nullable', 'string', 'max:255'], // Alias alternativo de campaign
            'campaign_pattern' => ['nullable', 'string', 'max:255'], // Retrocompatibilidad (deprecado)

            // Source flexible - acepta cualquier string para integraciones custom
            'source' => ['nullable', 'string', 'max:100'],

            // Campos adicionales
            'intention' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone is required',
            'name.required' => 'Name is required',
            'campaign_id.exists' => 'Campaign not found',
            'tags.array' => 'Tags must be an array',
            'tags.*.string' => 'Each tag must be a string',
        ];
    }

    /**
     * Prepara los datos antes de la validación
     * Normaliza 'args' si viene desde un payload envuelto
     */
    protected function prepareForValidation(): void
    {
        // Si el payload viene envuelto en { "name": "webhook_register_phone", "args": {...} }
        if ($this->has('args') && is_array($this->args)) {
            $this->merge($this->args);
        }

        // Normalizar 'campaign' como alias de 'campaign_pattern'
        if ($this->has('campaign') && ! $this->has('campaign_pattern')) {
            $this->merge(['campaign_pattern' => $this->campaign]);
        }

        // Normalizar teléfono
        if ($this->has('phone')) {
            // Obtener campaña si viene campaign_id
            $campaign = null;
            if ($this->has('campaign_id')) {
                $campaign = \App\Models\Campaign::find($this->campaign_id);
            }

            $normalizedPhone = \App\Helpers\PhoneHelper::normalizeForLead(
                $this->phone,
                $campaign
            );

            $this->merge([
                'phone' => $normalizedPhone,
            ]);
        }
    }
}
