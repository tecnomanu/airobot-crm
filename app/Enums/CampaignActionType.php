<?php

namespace App\Enums;

enum CampaignActionType: string
{
    case WHATSAPP = 'whatsapp';
    case CALL_AI = 'call_ai';
    case WEBHOOK_CRM = 'webhook_crm';
    case MANUAL_REVIEW = 'manual_review';
    case SKIP = 'skip';

    public function label(): string
    {
        return match($this) {
            self::WHATSAPP => 'Enviar WhatsApp',
            self::CALL_AI => 'Llamada con IA',
            self::WEBHOOK_CRM => 'Enviar a CRM',
            self::MANUAL_REVIEW => 'Revisión Manual',
            self::SKIP => 'No hacer nada',
        };
    }
}

