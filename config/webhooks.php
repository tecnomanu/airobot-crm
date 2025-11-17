<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webhook Security Token
    |--------------------------------------------------------------------------
    |
    | Token de seguridad para validar webhooks entrantes.
    | Las fuentes externas deben incluir este token en el header:
    | X-Webhook-Token: {token}
    |
    */

    'token' => env('WEBHOOK_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret (HMAC)
    |--------------------------------------------------------------------------
    |
    | Secret para validar firmas HMAC en webhooks entrantes.
    | Las fuentes externas deben incluir la firma en el header:
    | X-Webhook-Signature: sha256={hash}
    |
    */

    'secret' => env('WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Webhook Validation
    |--------------------------------------------------------------------------
    |
    | Habilitar/deshabilitar validación de webhooks.
    | Método: 'token' (simple) o 'hmac' (firma)
    |
    */

    'validation_enabled' => env('WEBHOOK_VALIDATION_ENABLED', true),
    'validation_method' => env('WEBHOOK_VALIDATION_METHOD', 'token'), // token o hmac

    /*
    |--------------------------------------------------------------------------
    | Webhook Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout en segundos para llamadas HTTP a webhooks de clientes.
    | Por defecto: 30 segundos
    |
    */

    'timeout' => env('WEBHOOK_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para reintentos automáticos de webhooks fallidos.
    |
    */

    'retry' => [
        'enabled' => env('WEBHOOK_RETRY_ENABLED', true),
        'attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
        'delay' => env('WEBHOOK_RETRY_DELAY', 60), // segundos
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed IPs
    |--------------------------------------------------------------------------
    |
    | Lista de IPs permitidas para webhooks (opcional).
    | Si está vacío, se permiten todas las IPs.
    |
    */

    'allowed_ips' => env('WEBHOOK_ALLOWED_IPS') ?
        explode(',', env('WEBHOOK_ALLOWED_IPS')) :
        [],

];
