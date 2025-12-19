<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ScrambleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only configure Scramble if it's installed (dev environment)
        if (!class_exists(\Dedoc\Scramble\Scramble::class)) {
            return;
        }

        // Configurar qué rutas documentar
        Scramble::routes(function (Route $route) {
            // Documentar todas las rutas que empiecen con api/
            return Str::startsWith($route->uri, 'api/');
        });

        // Personalizar información de la API (método moderno)
        Scramble::afterOpenApiGenerated(function ($openApi) {
            $openApi->info->title = 'AIRobot API';
            $openApi->info->description = '
# Documentación API de AIRobot

Esta documentación incluye todos los endpoints de la API.

## 🔐 Autenticación

Las rutas administrativas requieren **Laravel Sanctum**:
```
Authorization: Bearer {token}
```

## 📖 Grupos de Endpoints

### Webhooks Externos (Sin autenticación)

**Webhooks de Leads:**
- POST /api/webhooks/lead - Recibir leads desde fuentes externas (n8n, formularios, etc.)
- POST /api/webhooks/event - Webhook dinámico basado en eventos (Strategy pattern)
- GET /api/webhooks/events - Listar eventos disponibles

**Webhooks de Llamadas:**
- POST /api/webhooks/call - Webhook genérico de llamadas (legacy)
- POST /api/webhooks/retell-call - Eventos de llamadas desde Retell AI
- POST /api/webhooks/vapi-call - Eventos de llamadas desde Vapi
- POST /api/webhooks/call/{provider} - Webhook genérico con provider dinámico

**Webhooks de WhatsApp:**
- POST /api/webhooks/whatsapp-incoming - Mensajes entrantes de WhatsApp (Evolution API)

### API Administrativa (Requiere Sanctum)
- **Leads:** GET, POST, PUT, DELETE /api/leads/*
- **Campañas:** GET, POST, PUT, DELETE /api/campaigns/*
- **Clientes:** GET, POST, PUT, DELETE /api/clients/*
- **Call History:** GET /api/call-history/*
- **Reportes:** GET /api/reporting/*

## 📚 Características

- Todos los endpoints retornan JSON
- Validación automática con FormRequests
- Respuestas consistentes con Resources
- Paginación en listados
            ';
            $openApi->info->version = '1.0.0';
        });
    }
}
