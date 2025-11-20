<?php

declare(strict_types=1);

namespace App\Exceptions\Business;

use Exception;

/**
 * Excepción para errores de configuración que deben manejarse como SKIPPED
 * en lugar de FAILED (no es un error técnico, sino falta de configuración)
 */
class ConfigurationException extends Exception
{
    /**
     * Constructor
     */
    public function __construct(
        string $message = 'Configuration error',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
