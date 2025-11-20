<?php

declare(strict_types=1);

namespace App\Http\Requests\Source;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(SourceType::class)],
            'status' => ['nullable', Rule::enum(SourceStatus::class)],
            'client_id' => ['nullable', 'string', 'exists:clients,id'],
            'config' => ['required', 'array'],
            'redirect_to' => ['nullable', 'string'],

            // Validaciones específicas de WhatsApp
            'config.phone_number' => ['required_if:type,whatsapp,meta_whatsapp', 'string', 'max:20'],
            'config.provider' => ['nullable', 'string'],
            'config.instance_name' => ['required_if:type,whatsapp,meta_whatsapp', 'string'],
            'config.api_url' => ['required_if:type,whatsapp,meta_whatsapp', 'url'],
            'config.api_key' => ['required_if:type,whatsapp,meta_whatsapp', 'string'],

            // Validaciones específicas de Webhook
            'config.url' => ['required_if:type,webhook', 'url'],
            'config.method' => ['required_if:type,webhook', 'string', Rule::in(['GET', 'POST', 'PUT', 'PATCH'])],
            'config.secret' => ['nullable', 'string'],
            'config.payload_template' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la fuente es obligatorio',
            'type.required' => 'El tipo de fuente es obligatorio',
            'config.required' => 'La configuración es obligatoria',
            'config.phone_number.required_if' => 'El número de WhatsApp es obligatorio',
            'config.instance_name.required_if' => 'El nombre de instancia es obligatorio para fuentes de WhatsApp',
            'config.api_url.required_if' => 'La URL de API es obligatoria',
            'config.api_key.required_if' => 'La API Key es obligatoria',
            'config.url.required_if' => 'La URL del webhook es obligatoria',
            'config.method.required_if' => 'El método HTTP es obligatorio',
        ];
    }
}
