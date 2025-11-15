<?php

namespace App\Http\Requests\Client;

use App\Enums\ClientStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:clients,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:255'],
            'billing_info' => ['nullable', 'array'],
            'billing_info.tax_id' => ['nullable', 'string', 'max:50'],
            'billing_info.address' => ['nullable', 'string', 'max:500'],
            'billing_info.city' => ['nullable', 'string', 'max:100'],
            'billing_info.country' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ClientStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del cliente es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Ya existe un cliente con este email',
        ];
    }
}
