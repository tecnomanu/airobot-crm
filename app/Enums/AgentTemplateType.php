<?php

namespace App\Enums;

enum AgentTemplateType: string
{
    case APPOINTMENT = 'appointment';
    case SALES = 'sales';
    case SURVEY = 'survey';
    case SUPPORT = 'support';
    case QUALIFICATION = 'qualification';

    public function label(): string
    {
        return match ($this) {
            self::APPOINTMENT => 'Agendamiento de Citas',
            self::SALES => 'Ventas',
            self::SURVEY => 'Encuesta',
            self::SUPPORT => 'Soporte',
            self::QUALIFICATION => 'Calificación de Leads',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::APPOINTMENT => 'Agente especializado en agendar citas y coordinar horarios con leads',
            self::SALES => 'Agente enfocado en cerrar ventas y presentar productos/servicios',
            self::SURVEY => 'Agente para realizar encuestas y recopilar información',
            self::SUPPORT => 'Agente de atención al cliente y soporte técnico',
            self::QUALIFICATION => 'Agente para calificar y clasificar leads según criterios específicos',
        };
    }
}
