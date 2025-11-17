# 🔍 Verificar Número en Evolution API

## Problema Actual

Los mensajes de WhatsApp están llegando desde un número diferente al esperado:

- **Esperado**: `+5492944636430` (2944636430 - Argentina)
- **Recibido**: `+101666238013462` (número de sandbox/prueba)

---

## ✅ Verificar qué número está conectado en Evolution

### Opción 1: Via Panel Web

1. Ir a Evolution API Manager
2. Seleccionar instancia `LocalTesting`
3. Ver **Estado de Conexión** / **Info**
4. Verificar el número de teléfono conectado

### Opción 2: Via API

```bash
curl -X GET https://evolution.incubit.com.ar/instance/connectionState/LocalTesting \
  -H "apikey: B7A8B257977A-4A81-92CF-971D4C520A5C"
```

**Respuesta esperada:**
```json
{
  "instance": {
    "instanceName": "LocalTesting",
    "owner": "5492944636430@s.whatsapp.net",
    "profileName": "...",
    "profilePictureUrl": "..."
  }
}
```

---

## 🔧 Soluciones

### Solución 1: Reconectar con el número correcto

Si el número conectado NO es `2944636430`:

1. **Desconectar instancia actual**
   ```bash
   curl -X DELETE https://evolution.incubit.com.ar/instance/logout/LocalTesting \
     -H "apikey: B7A8B257977A-4A81-92CF-971D4C520A5C"
   ```

2. **Escanear QR nuevamente** con el celular que tiene el número `2944636430`

### Solución 2: Crear nueva instancia con el número correcto

```bash
curl -X POST https://evolution.incubit.com.ar/instance/create \
  -H "Content-Type: application/json" \
  -H "apikey: B7A8B257977A-4A81-92CF-971D4C520A5C" \
  -d '{
    "instanceName": "Production_2944636430",
    "qrcode": true,
    "integration": "WHATSAPP-BAILEYS"
  }'
```

Luego escanear el QR con el celular correcto.

### Solución 3: Usar el número que ya está conectado

Si prefieres trabajar con el número ya conectado (`+101666238013462`):

1. Ya está funcionando ✅
2. Los leads con ese número ya pueden recibir y responder
3. Para producción, cambiar al número real

---

## 🧪 Test Rápido

### Ver qué número está conectado:

```bash
# Ver info de la instancia
curl -X GET https://evolution.incubit.com.ar/instance/fetchInstances/LocalTesting \
  -H "apikey: B7A8B257977A-4A81-92CF-971D4C520A5C" | jq .
```

### Enviar mensaje de prueba:

```bash
curl -X POST https://evolution.incubit.com.ar/message/sendText/LocalTesting \
  -H "Content-Type: application/json" \
  -H "apikey: B7A8B257977A-4A81-92CF-971D4C520A5C" \
  -d '{
    "number": "5492944636430",
    "text": "Test de AIRobot - Si recibes este mensaje, responde con SI"
  }'
```

---

## 📋 Checklist de Verificación

- [ ] Verificar número conectado en Evolution
- [ ] Confirmar que el número es `2944636430`
- [ ] Si es diferente, reconectar con número correcto
- [ ] Actualizar configuración en AIRobot Source
- [ ] Enviar mensaje de prueba
- [ ] Verificar que llega al número correcto
- [ ] Responder desde WhatsApp
- [ ] Confirmar que el webhook procesa correctamente

---

## 💡 Nota Importante

**El número que aparece en los logs** (`+101666238013462`) indica que ese es el número conectado actualmente en Evolution.

Para que funcione con `+5492944636430`, necesitas:
1. Tener ese número en un celular con WhatsApp
2. Conectar ese celular a Evolution (escaneando QR)
3. Los mensajes saldrán y llegarán a ese número

---

## 🔗 Documentación Evolution API

- [Instances](https://doc.evolution-api.com/v2/pt/integrate/instances)
- [Connection](https://doc.evolution-api.com/v2/pt/integrate/connection)
- [Messages](https://doc.evolution-api.com/v2/pt/integrate/send-messages)

