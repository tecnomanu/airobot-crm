# 🗺️ Estructura de la API - AIRobot

## 📋 Tabla de Contenidos

1. [Webhooks Externos](#-webhooks-externos)
2. [API Administrativa](#-api-administrativa)
3. [Autenticación](#-autenticación)
4. [Ejemplos de Uso](#-ejemplos-de-uso)

---

## 📥 WEBHOOKS EXTERNOS

**Base URL:** `/api/webhooks/`  
**Autenticación:** `X-Webhook-Token` header  
**Uso:** Recibir datos de sistemas externos (n8n, proveedores, etc.)

### 📞 Leads - Ingreso de leads

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/webhooks/lead` | Registro directo de lead |
| POST | `/webhooks/event` | Webhook por eventos (Strategy pattern) |
| GET | `/webhooks/events` | Listar eventos disponibles |

### 📞 Llamadas - Proveedores de telefonía

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/webhooks/call` | Webhook genérico de llamadas |
| POST | `/webhooks/retell-call` | Eventos desde Retell AI |
| POST | `/webhooks/vapi-call` | Eventos desde Vapi |
| POST | `/webhooks/call/{provider}` | Webhook dinámico por proveedor |

### 💬 WhatsApp - Mensajes entrantes

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/webhooks/whatsapp-incoming` | Mensajes de WhatsApp (Evolution API) |

---

## 🔐 API ADMINISTRATIVA

**Base URL:** `/api/admin/`  
**Autenticación:** `Authorization: Bearer {token}` (Sanctum)  
**Uso:** Panel administrativo y operaciones internas

### 👥 Leads - Gestión de leads

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/admin/leads` | Listar todos los leads |
| GET | `/admin/leads/{id}` | Ver detalle de un lead |
| POST | `/admin/leads` | Crear nuevo lead |
| PUT | `/admin/leads/{id}` | Actualizar lead |
| DELETE | `/admin/leads/{id}` | Eliminar lead |

### 📢 Campañas - Gestión de campañas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/admin/campaigns` | Listar campañas |
| GET | `/admin/campaigns/{id}` | Ver detalle de campaña |
| POST | `/admin/campaigns` | Crear campaña |
| PUT | `/admin/campaigns/{id}` | Actualizar campaña |
| DELETE | `/admin/campaigns/{id}` | Eliminar campaña |

#### Templates de WhatsApp

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/admin/campaigns/{id}/templates` | Listar templates |
| POST | `/admin/campaigns/{id}/templates` | Crear template |
| PUT | `/admin/campaigns/{id}/templates/{templateId}` | Actualizar template |
| DELETE | `/admin/campaigns/{id}/templates/{templateId}` | Eliminar template |

### 🏢 Clientes - Gestión de clientes

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/admin/clients` | Listar clientes |
| GET | `/admin/clients/{id}` | Ver detalle de cliente |
| POST | `/admin/clients` | Crear cliente |
| PUT | `/admin/clients/{id}` | Actualizar cliente |
| DELETE | `/admin/clients/{id}` | Eliminar cliente |

#### Dispatch de Leads

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/admin/clients/{id}/leads/{leadId}/dispatch` | Enviar lead al cliente |
| GET | `/admin/clients/{id}/leads/{leadId}/dispatch-status` | Ver estado de envío |

### 📞 Historial de Llamadas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/admin/call-history` | Listar llamadas |
| GET | `/admin/call-history/{id}` | Ver detalle de llamada |

### 📊 Reportes y Métricas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/admin/reporting/metrics` | Métricas globales del dashboard |
| GET | `/admin/reporting/campaigns/performance` | Rendimiento de campañas |
| GET | `/admin/reporting/clients/{id}/overview` | Overview de cliente |
| GET | `/admin/reporting/clients/{id}/monthly-summary` | Resumen mensual de cliente |

---

## 🔑 AUTENTICACIÓN

### 1. Webhooks Externos

```bash
# Header requerido
X-Webhook-Token: tu_token_secreto

# Generar token
php artisan webhook:generate-token --show

# Configurar en .env
WEBHOOK_TOKEN=f473e656adfb70636cfe53336f0b9c8c...
```

### 2. API Administrativa

```bash
# Header requerido
Authorization: Bearer {sanctum_token}

# Generar token (desde Tinker o código)
$user = User::find(1);
$token = $user->createToken('panel-admin')->plainTextToken;
```

---

## 💡 EJEMPLOS DE USO

### Webhook - Registrar Lead

```bash
curl -X POST http://localhost:8001/api/webhooks/lead \
  -H "X-Webhook-Token: f473e656adfb70636cfe53336f0b9c8c..." \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "2944636430",
    "name": "Manuel",
    "city": "Buenos Aires",
    "option_selected": "1",
    "campaign": "direct-tv",
    "source": "ivr_rodrigo"
  }'
```

### Webhook - Evento con Strategy Pattern

```bash
curl -X POST http://localhost:8001/api/webhooks/event \
  -H "X-Webhook-Token: f473e656adfb70636cfe53336f0b9c8c..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "webhook_register_phone",
    "args": {
      "phone": "2215648523",
      "name": "Juan",
      "option_selected": "1",
      "campaign": "direct-tv"
    }
  }'
```

### API Admin - Listar Leads

```bash
curl -X GET http://localhost:8001/api/admin/leads \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Accept: application/json"
```

### API Admin - Crear Campaña

```bash
curl -X POST http://localhost:8001/api/admin/campaigns \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Campaña Verano 2025",
    "client_id": "uuid-123",
    "description": "Campaña promocional de verano",
    "status": "active"
  }'
```

---

## 📚 DOCUMENTACIÓN COMPLETA

- **Scramble OpenAPI:** http://localhost:8001/docs/api
- **Guía de Webhooks:** `WEBHOOK_QUICK_START.md`
- **Seguridad:** `WEBHOOK_SECURITY.md`

---

## 🎯 VENTAJAS DE ESTA ESTRUCTURA

✅ **Clara separación:** Webhooks externos vs API interna  
✅ **URLs semánticas:** `/admin/*` es obviamente administrativo  
✅ **Fácil de escalar:** Agregar nuevos endpoints es directo  
✅ **Bien documentada:** Nombres de rutas descriptivos  
✅ **Segura:** Cada sección con su autenticación apropiada  

---

## 🚀 PRÓXIMOS PASOS

1. Configurar rate limiting para webhooks
2. Agregar métricas de uso por endpoint
3. Implementar API versionada (`/api/v1/`, `/api/v2/`)
4. Agregar webhook de salida para clientes

