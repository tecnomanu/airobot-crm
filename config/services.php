<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Call Provider Services
    |--------------------------------------------------------------------------
    */

    'retell' => [
        'api_key' => env('RETELL_API_KEY'),
        'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
    ],

    'vapi' => [
        'api_key' => env('VAPI_API_KEY'),
        'webhook_secret' => env('VAPI_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Service (Evolution API)
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL'),
        'api_key' => env('WHATSAPP_API_KEY'),
        'webhook_token' => env('WHATSAPP_WEBHOOK_TOKEN'),
        'auto_reply_delay' => env('WHATSAPP_AUTO_REPLY_DELAY', 5), // Segundos antes de enviar auto-respuesta
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Service
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL'), // URL base personalizada (ej: OpenRouter)
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 150),
        'temperature' => env('OPENAI_TEMPERATURE', 0.3),
        'analyze_intentions' => env('OPENAI_ANALYZE_INTENTIONS', true), // Activar análisis IA
        'analysis_delay_seconds' => env('OPENAI_ANALYSIS_DELAY', 8), // Delay antes de analizar (debouncing)
        'use_keywords_first' => env('OPENAI_USE_KEYWORDS_FIRST', false), // Usar palabras clave antes de IA (puede causar falsos positivos)
    ],

];
