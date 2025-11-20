<?php

namespace App\Http\Requests\Campaign\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class WhatsappTemplateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|string|url',
            'is_default' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio',
            'name.required' => 'El nombre es obligatorio',
            'body.required' => 'El cuerpo del mensaje es obligatorio',
            'attachments.*.url' => 'Cada adjunto debe ser una URL válida',
        ];
    }
}
