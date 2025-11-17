#!/bin/bash

echo "🧪 Testing WhatsApp Incoming Webhook"
echo "===================================="
echo ""

# Simular mensaje entrante de Evolution API
curl -X POST http://localhost:8000/api/webhooks/whatsapp-incoming \
  -H "Content-Type: application/json" \
  -d '{
    "event": "messages.upsert",
    "instance": "LocalTesting",
    "data": {
      "key": {
        "remoteJid": "5492944636430@s.whatsapp.net",
        "fromMe": false,
        "id": "TEST_MESSAGE_123"
      },
      "pushName": "Test User",
      "message": {
        "conversation": "Sí, me interesa recibir más información!"
      },
      "messageType": "conversation",
      "messageTimestamp": '$(date +%s)'
    }
  }' | python3 -m json.tool 2>/dev/null || cat

echo ""
echo ""
echo "✅ Webhook enviado!"
echo ""
echo "Verificar logs:"
echo "  tail -10 storage/logs/laravel.log | grep WhatsApp"
echo ""
echo "Ver lead actualizado:"
echo "  php artisan tinker --execute=\"\\\$lead = \\App\\Models\\Lead::where('phone', '+5492944636430')->first(); echo 'Intent: ' . \\\$lead->intention_status?->value;\""

