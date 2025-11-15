<?php

declare(strict_types=1);

namespace App\DTOs\External;

/**
 * DTO para resultado de envío de webhook
 */
class WebhookResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly int $statusCode,
        public readonly string $responseBody,
        public readonly ?string $error = null,
        public readonly int $attempt = 1,
    ) {}

    /**
     * Crear desde respuesta exitosa
     */
    public static function success(int $statusCode, string $responseBody, int $attempt = 1): self
    {
        return new self(
            success: true,
            statusCode: $statusCode,
            responseBody: $responseBody,
            error: null,
            attempt: $attempt
        );
    }

    /**
     * Crear desde error
     */
    public static function failed(string $error, int $statusCode = 0, int $attempt = 1): self
    {
        return new self(
            success: false,
            statusCode: $statusCode,
            responseBody: '',
            error: $error,
            attempt: $attempt
        );
    }

    /**
     * Convertir a array para almacenar
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status_code' => $this->statusCode,
            'body' => strlen($this->responseBody) > 500 
                ? substr($this->responseBody, 0, 500) . '...' 
                : $this->responseBody,
            'error' => $this->error,
            'sent_at' => now()->toIso8601String(),
            'attempt' => $this->attempt,
        ];
    }
}

