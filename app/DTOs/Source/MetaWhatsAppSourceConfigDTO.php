<?php

declare(strict_types=1);

namespace App\DTOs\Source;

/**
 * DTO para configuración de fuente WhatsApp Business (Meta)
 */
class MetaWhatsAppSourceConfigDTO
{
    public function __construct(
        public readonly string $phone_number_id,
        public readonly string $access_token,
        public readonly string $verify_token,
        public readonly ?string $business_account_id = null,
        public readonly ?string $webhook_url = null,
    ) {}

    /**
     * Crea un DTO desde un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            phone_number_id: $data['phone_number_id'] ?? '',
            access_token: $data['access_token'] ?? '',
            verify_token: $data['verify_token'] ?? '',
            business_account_id: $data['business_account_id'] ?? null,
            webhook_url: $data['webhook_url'] ?? null,
        );
    }

    /**
     * Convierte el DTO a array
     */
    public function toArray(): array
    {
        return array_filter([
            'phone_number_id' => $this->phone_number_id,
            'access_token' => $this->access_token,
            'verify_token' => $this->verify_token,
            'business_account_id' => $this->business_account_id,
            'webhook_url' => $this->webhook_url,
        ], fn ($value) => $value !== null);
    }

    /**
     * Valida que todos los campos requeridos estén presentes
     */
    public function isValid(): bool
    {
        return ! empty($this->phone_number_id)
            && ! empty($this->access_token)
            && ! empty($this->verify_token);
    }
}
