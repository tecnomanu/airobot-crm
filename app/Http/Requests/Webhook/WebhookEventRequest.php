<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para webhooks basados en eventos con estructura name/args
 *
 * Formato esperado:
 * {
 *   "name": "webhook_register_phone",
 *   "args": {
 *     "phone": "...",
 *     "name": "...",
 *     ...
 *   }
 * }
 */
class WebhookEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Validar token/secret en header si es necesario
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'args' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Event name is required',
            'args.required' => 'Event arguments are required',
            'args.array' => 'Arguments must be an array',
        ];
    }
}
