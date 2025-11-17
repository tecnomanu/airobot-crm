<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhatsappIncomingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Validar token del webhook
        $token = $this->header('X-Webhook-Token');
        $expectedToken = config('services.whatsapp.webhook_token', env('WHATSAPP_WEBHOOK_TOKEN'));

        return $token && $token === $expectedToken;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'content' => 'required|string',
            'message_id' => 'nullable|string',
            'timestamp' => 'nullable|integer',
            'campaign_id' => 'nullable|string',
            'type' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'El teléfono es obligatorio',
            'content.required' => 'El contenido del mensaje es obligatorio',
        ];
    }
}
