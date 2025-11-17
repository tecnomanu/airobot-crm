<?php

declare(strict_types=1);

namespace App\Services\External;

use App\Models\Lead;
use App\Models\Source;

/**
 * Interfaz para envío de mensajes WhatsApp a través de Sources
 */
interface WhatsAppSenderInterface
{
    /**
     * Enviar mensaje de WhatsApp a un lead usando una fuente
     *
     * @param  Source  $source  Fuente configurada (WHATSAPP o META_WHATSAPP)
     * @param  Lead  $lead  Lead destinatario
     * @param  string  $body  Cuerpo del mensaje
     * @param  array  $attachments  Archivos adjuntos opcionales
     * @return array Respuesta de la API
     *
     * @throws \Exception Si el envío falla
     */
    public function sendMessage(
        Source $source,
        Lead $lead,
        string $body,
        array $attachments = []
    ): array;

    /**
     * Enviar mensaje con template/plantilla
     *
     * @param  Source  $source  Fuente configurada
     * @param  Lead  $lead  Lead destinatario
     * @param  string  $templateName  Nombre del template
     * @param  array  $variables  Variables para reemplazar en template
     * @return array Respuesta de la API
     *
     * @throws \Exception Si el envío falla
     */
    public function sendTemplate(
        Source $source,
        Lead $lead,
        string $templateName,
        array $variables = []
    ): array;
}
