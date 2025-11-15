# 🚀 Webhooks - Guía Rápida

## TL;DR - Inicio Rápido

### 1. Generar Token

```bash
php artisan webhook:generate-token --show
```

Copia el token generado y agrégalo a tu `.env`:

```env
WEBHOOK_TOKEN=f473e656adfb70636cfe53336f0b9c8c1e4564dfdcd4672faeded77bb9cbe5ef
WEBHOOK_VALIDATION_ENABLED=true
WEBHOOK_VALIDATION_METHOD=token
```

### 2. Usar el Token en tus Requests

```bash
curl --request POST \
  --url http://localhost:8001/api/webhooks/lead \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'X-Webhook-Token: TU_TOKEN_AQUI' \
  --data '{
    "phone": "2944636430",
    "name": "Manuel",
    "city": "Buenos Aires"
  }'
```

¡Listo! 🎉

---

## 📡 Webhooks Disponibles

### ✅ Webhooks Protegidos (requieren `X-Webhook-Token`)

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/webhooks/lead` | POST | Registrar lead directo |
| `/api/webhooks/event` | POST | Webhook por eventos (Strategy pattern) |
| `/api/webhooks/call` | POST | Registrar llamada genérica |
| `/api/webhooks/whatsapp-incoming` | POST | Mensaje entrante de WhatsApp |
| `/api/webhooks/retell-call` | POST | Webhook de Retell AI |
| `/api/webhooks/vapi-call` | POST | Webhook de Vapi |
| `/api/webhooks/call/{provider}` | POST | Webhook genérico por proveedor |

### 🔓 Sin protección

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/webhooks/events` | GET | Listar eventos disponibles |

---

## 🔑 Cómo Funciona la Validación

### Modo Desarrollo (Sin token en .env)

Si **NO** tienes `WEBHOOK_TOKEN` configurado:
- ✅ Los webhooks **funcionan normalmente**
- ⚠️ Se logea una advertencia
- 👍 Útil para desarrollo local

### Modo Producción (Con token)

Si **SÍ** tienes `WEBHOOK_TOKEN` configurado:
- 🔒 Solo requests con el token correcto funcionan
- ❌ Sin token o token inválido → `401 Unauthorized`
- 🛡️ Seguridad completa

---

## 🧪 Ejemplos Prácticos

### Ejemplo 1: Registrar Lead Directo

```bash
curl --request POST \
  --url http://localhost:8001/api/webhooks/lead \
  --header 'X-Webhook-Token: TU_TOKEN' \
  --header 'Content-Type: application/json' \
  --data '{
    "phone": "2944636430",
    "name": "Manuel",
    "city": "Buenos Aires",
    "option_selected": "1",
    "campaign": "direct-tv",
    "source": "ivr_rodrigo"
  }'
```

### Ejemplo 2: Webhook por Eventos

```bash
curl --request POST \
  --url http://localhost:8001/api/webhooks/event \
  --header 'X-Webhook-Token: TU_TOKEN' \
  --header 'Content-Type: application/json' \
  --data '{
    "name": "webhook_register_phone",
    "args": {
      "phone": "2215648523",
      "name": "Juan",
      "city": "La Plata",
      "option_selected": "1",
      "campaign": "direct-tv"
    }
  }'
```

### Ejemplo 3: n8n/Make/Zapier

En tu flujo de automatización, agrega estos headers:

```
Headers:
  - Content-Type: application/json
  - Accept: application/json
  - X-Webhook-Token: {{TU_TOKEN}}
```

---

## ⚠️ Errores Comunes

### ❌ "Unauthorized - Invalid webhook token"

**Problema:** Token faltante o incorrecto

**Solución:**
```bash
# Verifica tu token en .env
cat .env | grep WEBHOOK_TOKEN

# Asegúrate de enviarlo correctamente
curl ... --header 'X-Webhook-Token: VALOR_CORRECTO'
```

### ❌ Lead no se crea

**Problema:** Campaña no encontrada

**Solución:**
- Usa `campaign_id` si conoces el UUID
- Usa `campaign` con el `match_pattern` de la campaña
- Verifica que la campaña esté activa

---

## 📚 Documentación Completa

- **Seguridad detallada:** Ver `WEBHOOK_SECURITY.md`
- **Ejemplos de eventos:** Ver `WEBHOOK_EVENT_EXAMPLE.md`
- **Configuración avanzada:** Ver `config/webhooks.php`

---

## 🆘 Soporte Rápido

### ¿Sin token configurado?

```bash
php artisan webhook:generate-token --show
```

### ¿Qué eventos existen?

```bash
curl http://localhost:8001/api/webhooks/events
```

### ¿Funciona mi token?

```bash
# Con token correcto → 201
curl -I -X POST http://localhost:8001/api/webhooks/lead \
  -H "X-Webhook-Token: TU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"phone":"123","name":"test"}'

# Sin token o token malo → 401
```

---

¡Listo para recibir webhooks! 🎯

