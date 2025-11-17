# 📱 Configuración de Evolution API + AIRobot

## 🔗 URLs de Webhook

### Desarrollo (Local)
```
http://localhost:8000/api/webhooks/whatsapp-incoming
```

### Producción
```
https://tu-dominio.com/api/webhooks/whatsapp-incoming
```

---

## ⚙️ Configuración en Evolution API

### Opción 1: Via Panel Web de Evolution

1. Ir a Evolution API Manager
2. Seleccionar tu instancia (ej: `LocalTesting`)
3. Ir a **Configuración > Webhooks**
4. Configurar:
   - **URL**: `http://localhost:8000/api/webhooks/whatsapp-incoming`
   - **Eventos a escuchar**:
     - ✅ `messages.upsert` (mensajes entrantes)
     - ✅ `messages.update` (actualización de estado)
   - **Headers** (opcional):
     - `Content-Type: application/json`
     - `X-Webhook-Token: TU_TOKEN_SEGURO` (si usas autenticación)

### Opción 2: Via API de Evolution

```bash
curl -X POST https://evolution.incubit.com.ar/webhook/set/LocalTesting \
  -H "Content-Type: application/json" \
  -H "apikey: B7A8B257977A-4A81-92CF-971D4C520A5C" \
  -d '{
    "webhook": {
      "url": "http://localhost:8000/api/webhooks/whatsapp-incoming",
      "events": [
        "QRCODE_UPDATED",
        "CONNECTION_UPDATE",
        "MESSAGES_UPSERT",
        "MESSAGES_UPDATE"
      ],
      "webhook_by_events": false,
      "webhook_base64": false
    }
  }'
```

---

## 🔄 Flujo Completo

```
1. Lead recibe WhatsApp desde AIRobot
   └─> LeadInteraction creada (OUTBOUND)
   └─> intention_status: PENDING
   └─> intention_origin: WHATSAPP

2. Lead responde por WhatsApp
   └─> Evolution API detecta mensaje
   └─> Evolution envía webhook a AIRobot

3. AIRobot procesa webhook
   └─> WebhookWhatsappController->incoming()
   └─> WhatsAppIncomingMessageService->processIncomingMessage()
   └─> LeadInteraction creada (INBOUND)
   └─> intention_status: FINALIZED
   └─> intention: "interested" o "not_interested"

4. Si no responde en 24h
   └─> CheckPendingIntentsJob detecta timeout
   └─> intention: "no_response"
   └─> status: INVALID
```

---

## 🧪 Testing del Webhook

### Test 1: Verificar que AIRobot está escuchando

```bash
curl -X POST http://localhost:8000/api/webhooks/whatsapp-incoming \
  -H "Content-Type: application/json" \
  -d '{
    "event": "messages.upsert",
    "instance": "LocalTesting",
    "data": {
      "key": {
        "remoteJid": "5492944636430@s.whatsapp.net",
        "fromMe": false,
        "id": "TEST123"
      },
      "pushName": "Juan Test",
      "message": {
        "conversation": "Sí, me interesa!"
      }
    }
  }'
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "WhatsApp message processed successfully"
}
```

### Test 2: Verificar logs

```bash
tail -f storage/logs/laravel.log | grep WhatsApp
```

**Deberías ver:**
```
[INFO] Webhook WhatsApp recibido
[INFO] Mensaje entrante procesado
[INFO] Lead encontrado para teléfono
[INFO] Intent actualizado a FINALIZED
```

---

## 🚨 Troubleshooting

### Problema 1: Evolution no puede alcanzar localhost

**Solución**: Usar ngrok o túnel público

```bash
# Instalar ngrok
brew install ngrok  # macOS
# o descargar de https://ngrok.com

# Exponer puerto 8000
ngrok http 8000

# Usar la URL pública que te da (ej: https://abc123.ngrok.io)
# Configurar en Evolution: https://abc123.ngrok.io/api/webhooks/whatsapp-incoming
```

### Problema 2: Webhook no llega

**Verificar:**
1. ✅ Servidor Laravel corriendo (`php artisan serve`)
2. ✅ URL correcta configurada en Evolution
3. ✅ Firewall permite conexiones entrantes
4. ✅ Evolution tiene permisos para enviar webhooks

**Debug:**
```bash
# Ver todos los requests entrantes
tail -f storage/logs/laravel.log | grep "api/webhooks"
```

### Problema 3: Lead no se encuentra

**Verificar:**
1. ✅ Lead existe en DB con ese teléfono
2. ✅ Teléfono está normalizado correctamente (+5492944636430)
3. ✅ Lead tiene campaña asignada

**Query de verificación:**
```php
php artisan tinker
>>> \App\Models\Lead::where('phone', '+5492944636430')->first();
```

---

## 📊 Verificar Estado del Lead

```php
php artisan tinker

// Buscar lead por teléfono
$lead = \App\Models\Lead::where('phone', '+5492944636430')->first();

// Ver estado completo
echo "Status: " . $lead->intention_status?->value . "\n";
echo "Origin: " . $lead->intention_origin?->value . "\n";
echo "Intention: " . $lead->intention . "\n";

// Ver interacciones
$lead->interactions->each(function($i) {
    echo "{$i->channel->value} | {$i->direction->value} | {$i->content}\n";
});
```

---

## 🔐 Seguridad (Producción)

### 1. Activar Token de Webhook

En `.env`:
```env
WEBHOOK_TOKEN=tu_token_super_secreto_aqui
```

En Evolution, agregar header:
```
X-Webhook-Token: tu_token_super_secreto_aqui
```

### 2. Validar IP de Evolution (opcional)

En `app/Http/Middleware/ValidateWebhookToken.php`:
```php
$allowedIPs = ['IP_DE_EVOLUTION'];
if (!in_array($request->ip(), $allowedIPs)) {
    abort(403);
}
```

---

## 📝 Ejemplo de Payload Real de Evolution

```json
{
  "event": "messages.upsert",
  "instance": "LocalTesting",
  "data": {
    "key": {
      "remoteJid": "5492944636430@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB06C9665A61A38049F6A"
    },
    "pushName": "Juan Pérez",
    "message": {
      "conversation": "Hola, me interesa recibir más información"
    },
    "messageType": "conversation",
    "messageTimestamp": 1763262644,
    "instanceId": "091de83e-22cc-4780-b06b-826afab61c85",
    "source": "android"
  }
}
```

---

## ✅ Checklist de Setup

- [ ] Evolution API instalado y corriendo
- [ ] Instancia de WhatsApp conectada (QR escaneado)
- [ ] Webhook configurado en Evolution
- [ ] AIRobot corriendo (`php artisan serve`)
- [ ] Queue worker activo (`php artisan queue:work`)
- [ ] Test de webhook exitoso
- [ ] Lead de prueba creado
- [ ] WhatsApp enviado correctamente
- [ ] Respuesta de WhatsApp recibida y procesada

---

## 🎯 Siguiente Paso

Una vez configurado, puedes:

1. **Crear lead por webhook:**
```bash
bash test_lead_webhook.sh
```

2. **Responder desde WhatsApp** al mensaje recibido

3. **Verificar que se procesó:**
```bash
php artisan tinker
>>> $lead = \App\Models\Lead::latest()->first();
>>> $lead->intention_status->value; // Debería ser "finalized"
>>> $lead->interactions->count(); // Debería ser 2 (outbound + inbound)
```

---

## 📞 Comandos Útiles

```bash
# Ver últimos leads
php artisan tinker
>>> \App\Models\Lead::latest()->take(5)->get();

# Ver pending intents
>>> \App\Models\Lead::where('intention_status', 'pending')->count();

# Ejecutar check manual de timeouts
php artisan leads:check-pending-intents --timeout=24

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

---

¿Necesitas ayuda? Revisa los logs en `storage/logs/laravel.log` 🔍

