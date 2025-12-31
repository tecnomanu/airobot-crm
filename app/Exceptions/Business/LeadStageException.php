<?php

namespace App\Exceptions\Business;

use Exception;

class LeadStageException extends Exception
{
    public function __construct(string $message, int $code = 422)
    {
        parent::__construct($message, $code);
    }
}

