# 🔍 Instrucciones para Debugging del Webhook

## ✅ Configuración Completa

Se agregaron logs detallados para rastrear:
1. ✅ Payload completo del webhook
2. ✅ RemoteJid original de Evolution
3. ✅ Proceso de normalización del teléfono
4. ✅ Teléfono normalizado final

---

## 🎯 Pasos para Verificar

### 1. Iniciar Monitor en Tiempo Real

```bash
./monitor_whatsapp.sh
```

O manualmente:
```bash
tail -f storage/logs/laravel.log | grep -E "(DEBUG|Payload completo|remoteJid)"
```

### 2. Enviar Mensaje desde WhatsApp

- **Número destino**: 2944636430
- **Mensaje**: Cualquier texto (ej: "test")
- **Desde**: El celular que tiene WhatsApp conectado a Evolution

### 3. Revisar Logs

Deberías ver algo como:

```
[INFO] Webhook WhatsApp recibido
[INFO] Payload completo del webhook: {
  "event": "messages.upsert",
  "data": {
    "key": {
      "remoteJid": "542944636430@s.whatsapp.net" <-- ESTE ES EL NÚMERO CLAVE
    }
  }
}
[INFO] 🔍 DEBUG - Normalizando teléfono:
  remoteJid_original: 542944636430@s.whatsapp.net
  phone_extraido: 542944636430
[INFO] 🔍 DEBUG - Teléfono normalizado:
  phone_con_plus: +542944636430
  phone_normalizado: +5492944636430
```

---

## 🔎 Qué Buscar

### Caso 1: Número Correcto (Argentina)
```
remoteJid: 542944636430@s.whatsapp.net
```
✅ Debería encontrar el lead

### Caso 2: Número Incorrecto (Sandbox)
```
remoteJid: 101666238013462@s.whatsapp.net
```
❌ No encontrará el lead (a menos que crees uno con ese número)

---

## 🔧 Si el Número es Diferente

### Opción A: Crear lead con el número correcto

```bash
php artisan tinker

$lead = \App\Models\Lead::updateOrCreate(
    ['phone' => '+EL_NUMERO_QUE_APAREZCA'],
    [
        'name' => 'Test Real',
        'campaign_id' => '019a8a60-dcc9-7372-95a7-2a68c2755456',
        'option_selected' => '1',
        'status' => \App\Enums\LeadStatus::IN_PROGRESS,
        'intention_status' => \App\Enums\LeadIntentionStatus::PENDING,
        'intention_origin' => \App\Enums\LeadIntentionOrigin::WHATSAPP,
    ]
);
```

### Opción B: Reconectar Evolution con el número correcto

1. Ir a Evolution API Manager
2. Desconectar instancia actual
3. Escanear QR con el celular que tiene el número 2944636430

---

## 📊 Verificar Estado Final

```bash
php artisan tinker

# Ver último lead actualizado
$lead = \App\Models\Lead::latest('updated_at')->first();
echo "Phone: {$lead->phone}\n";
echo "Status: {$lead->intention_status?->value}\n";
echo "Intention: {$lead->intention}\n";

# Ver interacciones
$lead->interactions->each(function($i) {
    echo "{$i->direction->value}: {$i->content}\n";
});
```

---

## 🎯 Resultado Esperado

Si todo funciona correctamente:

```
✅ remoteJid: 542944636430@s.whatsapp.net
✅ phone_normalizado: +5492944636430
✅ Lead encontrado
✅ Intención actualizada: interested
✅ LeadInteraction creada: INBOUND
```

---

## 🚨 Problemas Comunes

### "Lead no encontrado"
- Verificar que el número en DB coincida
- Verificar formato: `+5492944636430` vs `+542944636430`
- Crear lead con el número exacto que aparece en logs

### "Número diferente al esperado"
- Evolution tiene otro número conectado
- Reconectar con el celular correcto
- O trabajar con el número actualmente conectado

### "Error al enviar auto-respuesta"
- Normal si el número no existe en WhatsApp
- El intent ya fue procesado correctamente
- Solo afecta la respuesta automática

---

## 📝 Limpiar Logs de Debug

Una vez resuelto el problema, puedes comentar los logs de debug en:
`app/Services/WhatsApp/WhatsAppIncomingMessageService.php` líneas 126-142

O dejarlos para debugging futuro (recomendado en desarrollo).

