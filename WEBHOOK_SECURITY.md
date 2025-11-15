# 🔐 Seguridad de Webhooks

Todos los webhooks en AIRobot están protegidos con validación de tokens para prevenir acceso no autorizado.

## 📋 Configuración Inicial

### 1. Generar Token Secreto

Ejecuta este comando para generar un token seguro:

```bash
php artisan tinker
```

Luego dentro de tinker:

```php
echo 'WEBHOOK_TOKEN=' . bin2hex(random_bytes(32));
// Resultado ejemplo: WEBHOOK_TOKEN=a8f5f167f44f4964e6c998dee827110c...
```

O usa este comando directo:

```bash
php -r "echo 'WEBHOOK_TOKEN=' . bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 2. Configurar en `.env`

Agrega estas variables a tu archivo `.env`:

```env
# Seguridad de Webhooks
WEBHOOK_VALIDATION_ENABLED=true
WEBHOOK_VALIDATION_METHOD=token  # token o hmac
WEBHOOK_TOKEN=a8f5f167f44f4964e6c998dee827110c...  # Tu token generado

# Opcional: Si usas HMAC en vez de token
# WEBHOOK_SECRET=otro_secret_para_hmac

# Opcional: IPs permitidas (separadas por comas)
# WEBHOOK_ALLOWED_IPS=192.168.1.100,10.0.0.5
```

---

## 🔑 Método 1: Token Simple (Recomendado)

Este es el método más simple y recomendado para la mayoría de casos.

### Configuración

```env
WEBHOOK_VALIDATION_METHOD=token
WEBHOOK_TOKEN=tu_token_secreto_aqui
```

### Cómo enviar requests

Incluye el token en el header `X-Webhook-Token`:

```bash
curl --request POST \
  --url http://localhost:8001/api/webhooks/lead \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'X-Webhook-Token: a8f5f167f44f4964e6c998dee827110c...' \
  --data '{
    "phone": "2944636430",
    "name": "Manuel",
    "city": "Buenos Aires"
  }'
```

### Ejemplos por lenguaje

**JavaScript (Node.js/n8n):**
```javascript
const axios = require('axios');

await axios.post('http://localhost:8001/api/webhooks/lead', {
  phone: '2944636430',
  name: 'Manuel',
  city: 'Buenos Aires'
}, {
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Webhook-Token': process.env.WEBHOOK_TOKEN
  }
});
```

**Python:**
```python
import requests
import os

response = requests.post(
    'http://localhost:8001/api/webhooks/lead',
    json={
        'phone': '2944636430',
        'name': 'Manuel',
        'city': 'Buenos Aires'
    },
    headers={
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Webhook-Token': os.getenv('WEBHOOK_TOKEN')
    }
)
```

**PHP:**
```php
$ch = curl_init('http://localhost:8001/api/webhooks/lead');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'phone' => '2944636430',
    'name' => 'Manuel',
    'city' => 'Buenos Aires'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'X-Webhook-Token: ' . getenv('WEBHOOK_TOKEN')
]);
$response = curl_exec($ch);
```

---

## 🔏 Método 2: HMAC Signature (Más Seguro)

Para mayor seguridad, usa firmas HMAC. El payload es firmado con un secret compartido.

### Configuración

```env
WEBHOOK_VALIDATION_METHOD=hmac
WEBHOOK_SECRET=tu_secret_hmac_aqui
```

### Cómo funciona

1. Tomas el **body completo** del request (JSON string)
2. Calculas un HMAC SHA-256 con tu secret
3. Envías la firma en el header `X-Webhook-Signature`

### Ejemplo de cálculo

**JavaScript:**
```javascript
const crypto = require('crypto');

const payload = JSON.stringify({
  phone: '2944636430',
  name: 'Manuel'
});

const secret = process.env.WEBHOOK_SECRET;
const signature = 'sha256=' + crypto
  .createHmac('sha256', secret)
  .update(payload)
  .digest('hex');

// signature = "sha256=a1b2c3d4..."
```

**Python:**
```python
import hmac
import hashlib
import json

payload = json.dumps({
    'phone': '2944636430',
    'name': 'Manuel'
})

secret = os.getenv('WEBHOOK_SECRET').encode()
signature = 'sha256=' + hmac.new(
    secret,
    payload.encode(),
    hashlib.sha256
).hexdigest()
```

**PHP:**
```php
$payload = json_encode([
    'phone' => '2944636430',
    'name' => 'Manuel'
]);

$secret = getenv('WEBHOOK_SECRET');
$signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
```

### Request completo con HMAC

```bash
curl --request POST \
  --url http://localhost:8001/api/webhooks/lead \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'X-Webhook-Signature: sha256=a1b2c3d4e5f6...' \
  --data '{"phone":"2944636430","name":"Manuel"}'
```

---

## 🧪 Modo Desarrollo (Sin Validación)

Para desarrollo local, puedes **deshabilitar temporalmente** la validación:

```env
WEBHOOK_VALIDATION_ENABLED=false
```

⚠️ **IMPORTANTE:** NUNCA desactives esto en producción!

---

## 🔍 Respuestas de Error

### Token Inválido o Faltante (401)

```json
{
  "success": false,
  "message": "Unauthorized - Invalid webhook token"
}
```

### Firma HMAC Inválida (401)

```json
{
  "success": false,
  "message": "Unauthorized - Invalid webhook signature"
}
```

---

## 🛡️ Seguridad Adicional

### Limitar por IP

Puedes restringir webhooks solo a IPs específicas:

```env
WEBHOOK_ALLOWED_IPS=192.168.1.100,10.0.0.5,203.0.113.10
```

### Rate Limiting

Considera agregar rate limiting a las rutas de webhook:

```php
// En routes/api.php
Route::prefix('webhooks')
    ->middleware([
        \App\Http\Middleware\ValidateWebhookSignature::class,
        'throttle:60,1' // 60 requests por minuto
    ])
    ->group(function () {
        // ... rutas
    });
```

---

## 📚 Endpoints Protegidos

Todos estos endpoints requieren autenticación:

- ✅ `POST /api/webhooks/lead`
- ✅ `POST /api/webhooks/call`
- ✅ `POST /api/webhooks/event`
- ✅ `POST /api/webhooks/whatsapp-incoming`
- ✅ `POST /api/webhooks/retell-call`
- ✅ `POST /api/webhooks/vapi-call`
- ✅ `POST /api/webhooks/call/{provider}`

### Sin protección (solo para debugging):

- 🔓 `GET /api/webhooks/events` - Lista eventos disponibles

---

## 🔧 Troubleshooting

### ❌ "Token no configurado, permitiendo acceso"

**Causa:** No hay `WEBHOOK_TOKEN` en `.env`

**Solución:** Genera y configura un token como se explicó arriba.

### ❌ "Invalid webhook token"

**Causa:** El token enviado no coincide con el configurado

**Solución:** Verifica que estés enviando el token correcto en el header `X-Webhook-Token`.

### ❌ "Missing webhook signature"

**Causa:** Estás usando HMAC pero no enviaste el header `X-Webhook-Signature`

**Solución:** Calcula y envía la firma HMAC correctamente.

---

## 📝 Checklist de Producción

Antes de ir a producción:

- [ ] Token generado con al menos 32 bytes de entropía
- [ ] `WEBHOOK_VALIDATION_ENABLED=true` en `.env` de producción
- [ ] Token configurado en n8n/integraciones externas
- [ ] Logs monitoreados para intentos de acceso no autorizado
- [ ] Rate limiting configurado
- [ ] (Opcional) IPs permitidas configuradas
- [ ] Tokens rotados periódicamente (cada 3-6 meses)

