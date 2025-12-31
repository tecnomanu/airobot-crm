<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageUsers();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_seller' => ['boolean'],
            'client_id' => ['nullable', 'uuid', 'exists:clients,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido',
            'email.required' => 'El email es requerido',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Ya existe un usuario con ese email',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'role.required' => 'El rol es requerido',
            'client_id.exists' => 'El cliente seleccionado no existe',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_seller' => $this->boolean('is_seller'),
        ]);
    }
}

