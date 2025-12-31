<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * LeadCloseReason represents the final outcome when a lead is closed.
 *
 * Used in conjunction with LeadStage::CLOSED to provide
 * detailed information about why/how the lead was closed.
 */
enum LeadCloseReason: string
{
    // Positive outcomes
    case INTERESTED = 'interested';
    case QUALIFIED = 'qualified';
    case CONVERTED = 'converted';

    // Negative outcomes
    case NOT_INTERESTED = 'not_interested';
    case DISQUALIFIED = 'disqualified';

    // Contact issues
    case NO_RESPONSE = 'no_response';
    case INVALID_NUMBER = 'invalid_number';
    case DNC = 'dnc'; // Do Not Call

    // Special cases
    case CALLBACK_REQUESTED = 'callback_requested';
    case DUPLICATE = 'duplicate';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::INTERESTED => 'Interesado',
            self::QUALIFIED => 'Calificado',
            self::CONVERTED => 'Convertido',
            self::NOT_INTERESTED => 'No Interesado',
            self::DISQUALIFIED => 'Descalificado',
            self::NO_RESPONSE => 'Sin Respuesta',
            self::INVALID_NUMBER => 'Número Inválido',
            self::DNC => 'No Contactar',
            self::CALLBACK_REQUESTED => 'Solicita Callback',
            self::DUPLICATE => 'Duplicado',
            self::OTHER => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INTERESTED, self::QUALIFIED, self::CONVERTED => 'green',
            self::NOT_INTERESTED, self::DISQUALIFIED => 'red',
            self::NO_RESPONSE, self::INVALID_NUMBER => 'orange',
            self::DNC => 'red',
            self::CALLBACK_REQUESTED => 'blue',
            self::DUPLICATE, self::OTHER => 'gray',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INTERESTED => 'Lead showed interest in the product/service',
            self::QUALIFIED => 'Lead meets all qualification criteria',
            self::CONVERTED => 'Lead converted to customer/sale',
            self::NOT_INTERESTED => 'Lead explicitly declined',
            self::DISQUALIFIED => 'Lead does not meet criteria',
            self::NO_RESPONSE => 'No response after multiple attempts',
            self::INVALID_NUMBER => 'Phone number is invalid or disconnected',
            self::DNC => 'Lead requested not to be contacted',
            self::CALLBACK_REQUESTED => 'Lead asked to be called back later',
            self::DUPLICATE => 'Duplicate entry of existing lead',
            self::OTHER => 'Other reason (see notes)',
        };
    }

    /**
     * Whether this reason is considered a positive outcome.
     */
    public function isPositive(): bool
    {
        return in_array($this, [
            self::INTERESTED,
            self::QUALIFIED,
            self::CONVERTED,
        ], true);
    }

    /**
     * Whether this reason triggers dispatch to client (webhook/sheet).
     */
    public function shouldDispatch(): bool
    {
        return in_array($this, [
            self::INTERESTED,
            self::QUALIFIED,
            self::CONVERTED,
            self::NOT_INTERESTED,
            self::DISQUALIFIED,
            self::NO_RESPONSE,
        ], true);
    }

    /**
     * Map to intention type for dispatch configuration lookup.
     */
    public function toIntentionType(): ?string
    {
        return match ($this) {
            self::INTERESTED, self::QUALIFIED, self::CONVERTED => 'interested',
            self::NOT_INTERESTED, self::DISQUALIFIED => 'not_interested',
            self::NO_RESPONSE => 'no_response',
            default => null,
        };
    }

    /**
     * Get all values as array for validation rules.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options for dropdown/select UI.
     */
    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}

