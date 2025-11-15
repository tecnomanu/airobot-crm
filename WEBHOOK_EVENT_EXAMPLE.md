# 📡 Webhooks - Guía de Uso

## 1️⃣ Webhook Directo: `/api/webhooks/lead`

Este webhook acepta leads directamente con una estructura simple.

### Estructura del Payload

```json
{
  "phone": "2944636430",
  "name": "Manuel",
  "city": "Buenos Aires",
  "option_selected": "1",
  "campaign": "direct-tv",
  "source": "ivr_rodrigo",
  "notes": "Cliente interesado",
  "tags": ["direct-tv", "rodrigo"]
}
```

### Campos

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `phone` | string | ✅ Sí | Teléfono del lead |
| `name` | string | ❌ No | Nombre del lead |
| `city` | string | ❌ No | Ciudad |
| `option_selected` | string | ❌ No | Opción seleccionada: `1`, `2`, `i`, `t` |
| `campaign` | string | ❌ No | Match pattern de la campaña |
| `campaign_id` | uuid | ❌ No | ID directo de la campaña |
| `source` | string | ❌ No | Fuente del lead (acepta cualquier string) |
| `intention` | string | ❌ No | Intención del lead |
| `notes` | string | ❌ No | Notas adicionales |
| `tags` | array | ❌ No | Array de strings |

### Ejemplo con cURL

```bash
curl --request POST \
  --url http://localhost:8001/api/webhooks/lead \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "phone": "2944636430",
  "name": "Manuel",
  "city": "Buenos Aires",
  "option_selected": "1",
  "campaign": "direct-tv",
  "source": "ivr_rodrigo",
  "notes": "Nada",
  "tags": ["direct-tv", "rodrigo"]
}'
```

---

## 2️⃣ Webhook por Eventos: `/api/webhooks/event`

Este webhook usa el **patrón Strategy** para procesar diferentes tipos de eventos de forma desacoplada.

### Estructura del Payload

```json
{
  "name": "webhook_register_phone",
  "args": {
    "phone": "2215648523",
    "name": "Juan",
    "city": "La Plata",
    "option_selected": "1",
    "campaign": "direct-tv"
  }
}
```

### Campos Principales

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `name` | string | ✅ Sí | Nombre del evento (ej: `webhook_register_phone`) |
| `args` | object | ✅ Sí | Argumentos del evento (varía según el tipo) |

### Argumentos para `webhook_register_phone`

Los mismos que el webhook directo:

- `phone` (requerido)
- `name`, `city`, `option_selected`, `campaign`, `source`, `notes`, `tags` (opcionales)

### Ejemplo con cURL

```bash
curl --request POST \
  --url http://localhost:8001/api/webhooks/event \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
  "name": "webhook_register_phone",
  "args": {
    "phone": "2215648523",
    "name": "Juan",
    "city": "La Plata",
    "option_selected": "1",
    "campaign": "direct-tv",
    "source": "ivr_demo",
    "tags": ["demo", "ivr"]
  }
}'
```

---

## 🔍 Listar Eventos Disponibles

```bash
curl --request GET \
  --url http://localhost:8001/api/webhooks/events \
  --header 'Accept: application/json'
```

**Respuesta:**

```json
{
  "success": true,
  "message": "Available webhook events",
  "events": [
    "webhook_register_phone"
  ],
  "total": 1
}
```

---

## 🎯 ¿Cuándo usar cada uno?

### Usa `/api/webhooks/lead` cuando:
- Integración simple y directa
- Solo necesitas registrar leads
- No requieres lógica compleja de eventos

### Usa `/api/webhooks/event` cuando:
- Necesitas múltiples tipos de eventos
- Quieres extensibilidad (agregar nuevas estrategias)
- Sistema complejo con diferentes flujos
- Payload viene con estructura `name`/`args`

---

## 🔧 Agregar Nuevos Eventos

### Paso 1: Crear la estrategia

```php
// app/Services/Webhook/Strategies/MiNuevoEventoStrategy.php
class MiNuevoEventoStrategy implements WebhookEventStrategyInterface
{
    public function getEventName(): string
    {
        return 'mi_nuevo_evento';
    }

    public function handle(array $args): JsonResponse
    {
        // Tu lógica aquí
    }

    public function validate(array $args): array
    {
        // Validaciones
    }
}
```

### Paso 2: Registrar en el ServiceProvider

```php
// app/Providers/WebhookEventServiceProvider.php
private function registerStrategies(WebhookEventManager $manager, $app): void
{
    $manager->registerStrategy($app->make(RegisterPhoneEventStrategy::class));
    $manager->registerStrategy($app->make(MiNuevoEventoStrategy::class)); // ⬅️ Agregar aquí
}
```

¡Listo! Tu nuevo evento estará disponible automáticamente.

---

## 📝 Respuestas

### Éxito (201/200)

```json
{
  "success": true,
  "message": "Lead received and processed successfully",
  "data": {
    "id": "uuid",
    "phone": "2944636430",
    "name": "Manuel",
    "campaign": {...},
    ...
  },
  "is_new": true
}
```

### Error de Validación (422)

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "phone": ["Phone is required"]
  }
}
```

### Evento Desconocido (400)

```json
{
  "success": false,
  "message": "Unknown event",
  "error": "No handler found for event: mi_evento_inexistente",
  "available_events": ["webhook_register_phone"]
}
```

