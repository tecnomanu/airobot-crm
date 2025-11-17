<?php

namespace App\Services\CallProvider;

use App\DTOs\CallProvider\CallEventDTO;

/**
 * Interface para servicios de proveedores de llamadas
 * Cada proveedor (Retell, Vapi, etc.) implementará esta interface
 */
interface CallProviderServiceInterface
{
    /**
     * Parsear el payload del webhook y convertirlo a CallEventDTO
     */
    public function parseWebhook(array $payload): CallEventDTO;

    /**
     * Validar la firma/autenticación del webhook (si aplica)
     */
    public function validateWebhookSignature(array $headers, string $rawBody): bool;

    /**
     * Obtener el nombre del proveedor
     */
    public function getProviderName(): string;
}
