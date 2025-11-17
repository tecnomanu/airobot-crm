<?php

declare(strict_types=1);

namespace App\DTOs\Source;

/**
 * DTO para configuración de fuente Webhook HTTP
 */
class WebhookSourceConfigDTO
{
    public function __construct(
        public readonly string $url,
        public readonly string $method,
        public readonly string $secret,
        public readonly ?array $headers = null,
        public readonly ?string $payload_template = null,
        public readonly ?int $timeout = 30,
        public readonly ?int $retry_attempts = 3,
    ) {}

    /**
     * Crea un DTO desde un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'] ?? '',
            method: strtoupper($data['method'] ?? 'POST'),
            secret: $data['secret'] ?? '',
            headers: $data['headers'] ?? null,
            payload_template: $data['payload_template'] ?? null,
            timeout: $data['timeout'] ?? 30,
            retry_attempts: $data['retry_attempts'] ?? 3,
        );
    }

    /**
     * Convierte el DTO a array
     */
    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'method' => $this->method,
            'secret' => $this->secret,
            'headers' => $this->headers,
            'payload_template' => $this->payload_template,
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retry_attempts,
        ], fn ($value) => $value !== null);
    }

    /**
     * Valida que todos los campos requeridos estén presentes
     */
    public function isValid(): bool
    {
        return ! empty($this->url)
            && ! empty($this->method)
            && ! empty($this->secret)
            && filter_var($this->url, FILTER_VALIDATE_URL) !== false
            && in_array($this->method, ['GET', 'POST', 'PUT', 'PATCH']);
    }
}
