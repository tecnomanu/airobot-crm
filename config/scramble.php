<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     |--------------------------------------------------------------------------
     | API Externa - Documentación Pública
     |--------------------------------------------------------------------------
     | Documentación para proveedores externos, webhooks de entrada, etc.
     | URL: /docs/api-external
     */
    'external' => [
        'enabled' => true,
        'api_path' => 'api',
        'api_domain' => null,
        'export_path' => 'api-external.json',
        
        'info' => [
            'version' => env('API_VERSION', '1.0.0'),
            'title' => 'AIRobot API Externa',
            'description' => '
# API Externa de AIRobot

Documentación para integraciones externas y webhooks de entrada.

## Webhooks Disponibles

### Webhooks de Leads
- **POST /api/webhooks/lead** - Recibir leads desde fuentes externas
- **POST /api/webhooks/whatsapp-incoming** - Mensajes entrantes de WhatsApp

### Webhooks de Llamadas
- **POST /api/webhooks/retell-call** - Eventos de llamadas desde Retell AI
- **POST /api/webhooks/vapi-call** - Eventos de llamadas desde Vapi
- **POST /api/webhooks/call/{provider}** - Webhook genérico para proveedores

## Autenticación

Los webhooks usan validación por token en headers:
- `X-Webhook-Token`: Token de autenticación del webhook
- `x-retell-signature`: Firma HMAC para webhooks de Retell

## Futuros Endpoints

- API pública para que clientes consulten sus datos
- Webhooks de salida para entrega de leads procesados
            ',
        ],
        
        'ui' => [
            'title' => 'AIRobot - API Externa',
            'theme' => 'light',
            'hide_try_it' => false,
            'hide_schemas' => false,
            'logo' => '',
            'try_it_credentials_policy' => 'omit',
            'layout' => 'responsive',
        ],
        
        'servers' => null,
        'middleware' => ['web'],
        
        // Solo documentar rutas de api-external.php
        'routes_path' => 'routes/api-external.php',
    ],

    /*
     |--------------------------------------------------------------------------
     | API Administrativa - Documentación Interna
     |--------------------------------------------------------------------------
     | Documentación para uso interno, panel de administración, scripts, etc.
     | URL: /docs/api-admin
     */
    'admin' => [
        'enabled' => true,
        'api_path' => 'api',
        'api_domain' => null,
        'export_path' => 'api-admin.json',
        
        'info' => [
            'version' => env('API_VERSION', '1.0.0'),
            'title' => 'AIRobot API Administrativa',
            'description' => '
# API Administrativa de AIRobot

Documentación para uso interno y administrativo.

## Autenticación

Todas las rutas requieren autenticación con **Laravel Sanctum**.

```
Authorization: Bearer {token}
```

## Grupos de Endpoints

### Gestión de Leads
CRUD completo de leads con filtros avanzados.

### Gestión de Campañas
Administración de campañas, agentes de IA y templates de WhatsApp.

### Gestión de Clientes
CRUD de clientes y sus métricas.

### Historial de Llamadas
Consulta de llamadas registradas desde proveedores externos.

### Dispatch de Leads
Envío de leads procesados a webhooks de clientes.

### Reportes y Métricas
Estadísticas globales, por campaña y por cliente.

## Endpoints Futuros

- Automatizaciones de leads
- Estadísticas en tiempo real
- Exportación de datos
- Gestión de API Keys
            ',
        ],
        
        'ui' => [
            'title' => 'AIRobot - API Admin (Interno)',
            'theme' => 'light',
            'hide_try_it' => false,
            'hide_schemas' => false,
            'logo' => '',
            'try_it_credentials_policy' => 'include',
            'layout' => 'responsive',
        ],
        
        'servers' => null,
        'middleware' => [
            'web',
            RestrictedDocsAccess::class,
        ],
        
        // Solo documentar rutas de api-admin.php
        'routes_path' => 'routes/api-admin.php',
    ],

    /*
     |--------------------------------------------------------------------------
     | Configuración Global
     |--------------------------------------------------------------------------
     */
    
    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,
    'extensions' => [],
];
