<?php

declare(strict_types=1);

namespace App\Http\Requests\Source;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(SourceType::class)],
            'status' => ['sometimes', Rule::enum(SourceStatus::class)],
            'client_id' => ['nullable', 'string', 'exists:clients,id'],
            'config' => ['sometimes', 'array'],
            'redirect_to' => ['nullable', 'string'],
            
            // Validaciones específicas (si se actualiza type o config)
            'config.instance_name' => ['sometimes', 'string'],
            'config.api_url' => ['sometimes', 'url'],
            'config.api_key' => ['sometimes', 'string'],
            'config.url' => ['sometimes', 'url'],
            'config.method' => ['sometimes', 'string', Rule::in(['GET', 'POST', 'PUT', 'PATCH'])],
            'config.secret' => ['nullable', 'string'],
            'config.payload_template' => ['nullable', 'string'],
        ];
    }
}

