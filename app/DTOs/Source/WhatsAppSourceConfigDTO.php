<?php

declare(strict_types=1);

namespace App\DTOs\Source;

/**
 * DTO para configuración de fuente WhatsApp (Evolution API)
 */
class WhatsAppSourceConfigDTO
{
    public function __construct(
        public readonly string $instance_name,
        public readonly string $api_url,
        public readonly string $api_key,
        public readonly ?string $phone_number = null,
        public readonly ?string $webhook_url = null,
    ) {}

    /**
     * Crea un DTO desde un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            instance_name: $data['instance_name'] ?? '',
            api_url: $data['api_url'] ?? '',
            api_key: $data['api_key'] ?? '',
            phone_number: $data['phone_number'] ?? null,
            webhook_url: $data['webhook_url'] ?? null,
        );
    }

    /**
     * Convierte el DTO a array
     */
    public function toArray(): array
    {
        return array_filter([
            'instance_name' => $this->instance_name,
            'api_url' => $this->api_url,
            'api_key' => $this->api_key,
            'phone_number' => $this->phone_number,
            'webhook_url' => $this->webhook_url,
        ], fn ($value) => $value !== null);
    }

    /**
     * Valida que todos los campos requeridos estén presentes
     */
    public function isValid(): bool
    {
        return ! empty($this->instance_name)
            && ! empty($this->api_url)
            && ! empty($this->api_key);
    }
}
