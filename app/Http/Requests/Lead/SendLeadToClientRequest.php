<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para disparar el envío de un lead al webhook del cliente
 * Normalmente no requiere datos adicionales, solo el ID del lead en la ruta
 */
class SendLeadToClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Opcional: permitir override de datos
            'custom_payload' => ['nullable', 'array'],
            'force_resend' => ['nullable', 'boolean'], // Reenviar aunque ya se haya enviado
        ];
    }
}
