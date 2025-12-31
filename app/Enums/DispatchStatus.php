<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * DispatchStatus represents the status of a dispatch attempt.
 */
enum DispatchStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case RETRYING = 'retrying';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::SUCCESS => 'Exitoso',
            self::FAILED => 'Fallido',
            self::RETRYING => 'Reintentando',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::SUCCESS => 'green',
            self::FAILED => 'red',
            self::RETRYING => 'yellow',
        };
    }

    /**
     * Whether this dispatch can be retried.
     */
    public function canRetry(): bool
    {
        return $this === self::FAILED;
    }
}

