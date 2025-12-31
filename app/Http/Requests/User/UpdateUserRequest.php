<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageUsers();
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['sometimes', 'nullable', Password::min(8)],
            'role' => ['sometimes', Rule::enum(UserRole::class)],
            'is_seller' => ['sometimes', 'boolean'],
            'client_id' => ['sometimes', 'nullable', 'uuid', 'exists:clients,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Ya existe un usuario con ese email',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'client_id.exists' => 'El cliente seleccionado no existe',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_seller')) {
            $this->merge([
                'is_seller' => $this->boolean('is_seller'),
            ]);
        }
    }
}

