<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tipos de fuentes/conectores externos.
 *
 * Los valores se guardan como string en DB para permitir
 * agregar nuevos tipos sin migraciones.
 */
enum SourceType: string
{
    case WHATSAPP = 'whatsapp';
    case WEBHOOK = 'webhook';
    case META_WHATSAPP = 'meta_whatsapp';
    case FACEBOOK_LEAD_ADS = 'facebook_lead_ads';
    case GOOGLE_ADS = 'google_ads';

    /**
     * Obtiene label legible para UI
     */
    public function label(): string
    {
        return match ($this) {
            self::WHATSAPP => 'WhatsApp (Evolution API)',
            self::WEBHOOK => 'Webhook HTTP',
            self::META_WHATSAPP => 'WhatsApp Business (Meta)',
            self::FACEBOOK_LEAD_ADS => 'Facebook Lead Ads',
            self::GOOGLE_ADS => 'Google Ads',
        };
    }

    /**
     * Obtiene el tipo base (sin proveedor)
     */
    public function typeLabel(): string
    {
        return match ($this) {
            self::WHATSAPP => 'WhatsApp',
            self::WEBHOOK => 'Webhook',
            self::META_WHATSAPP => 'WhatsApp',
            self::FACEBOOK_LEAD_ADS => 'Facebook Lead Ads',
            self::GOOGLE_ADS => 'Google Ads',
        };
    }

    /**
     * Obtiene el proveedor específico
     */
    public function provider(): string
    {
        return match ($this) {
            self::WHATSAPP => 'Evolution API',
            self::WEBHOOK => 'HTTP',
            self::META_WHATSAPP => 'Meta Business',
            self::FACEBOOK_LEAD_ADS => 'Meta Ads',
            self::GOOGLE_ADS => 'Google',
        };
    }

    /**
     * Verifica si el tipo es WhatsApp (cualquier proveedor)
     */
    public function isWhatsApp(): bool
    {
        return in_array($this, [
            self::WHATSAPP,
            self::META_WHATSAPP,
        ]);
    }

    /**
     * Verifica si el tipo es Webhook
     */
    public function isWebhook(): bool
    {
        return $this === self::WEBHOOK;
    }

    /**
     * Retorna los campos requeridos en config para cada tipo
     */
    public function requiredConfigFields(): array
    {
        return match ($this) {
            self::WHATSAPP => ['instance_name', 'api_url', 'api_key'],
            self::WEBHOOK => ['url', 'method', 'secret'],
            self::META_WHATSAPP => ['phone_number_id', 'access_token', 'verify_token'],
            self::FACEBOOK_LEAD_ADS => ['page_id', 'access_token'],
            self::GOOGLE_ADS => ['customer_id', 'conversion_action_id', 'developer_token'],
        };
    }

    /**
     * Verifica si un tipo es de mensajería
     */
    public function isMessaging(): bool
    {
        return in_array($this, [
            self::WHATSAPP,
            self::META_WHATSAPP,
        ]);
    }

    /**
     * Verifica si un tipo es de ads/publicidad
     */
    public function isAdvertising(): bool
    {
        return in_array($this, [
            self::FACEBOOK_LEAD_ADS,
            self::GOOGLE_ADS,
        ]);
    }
}
