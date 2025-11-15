<?php

namespace App\Enums;

enum WebhookMethod: string
{
    case POST = 'POST';
    case PUT = 'PUT';

    public function label(): string
    {
        return $this->value;
    }
}

