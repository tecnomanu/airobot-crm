<?php

declare(strict_types=1);

namespace App\Exceptions\Business;

use Exception;

/**
 * Excepción para errores de validación de lógica de negocio
 */
class ValidationException extends Exception
{
    /**
     * Constructor
     */
    public function __construct(
        string $message = 'Validation error',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
