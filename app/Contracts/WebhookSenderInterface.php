<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\External\WebhookResultDTO;
use App\Models\Lead\Lead;
use App\Models\Integration\Source;

/**
 * Interfaz para envío de webhooks a destinos externos (CRMs, etc.)
 */
interface WebhookSenderInterface
{
    /**
     * Enviar lead a destino configurado en Source
     *
     * @param  Source  $source  Fuente tipo WEBHOOK con config (url, method, secret)
     * @param  Lead  $lead  Lead a enviar
     * @param  array  $extraPayload  Datos adicionales para incluir en el payload
     * @return WebhookResultDTO Resultado del envío
     *
     * @throws \Exception Si el envío falla
     */
    public function sendLeadToDestination(
        Source $source,
        Lead $lead,
        array $extraPayload = []
    ): WebhookResultDTO;
}
