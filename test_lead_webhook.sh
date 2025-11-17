#!/bin/bash

# Test de webhook para crear lead y activar WhatsApp automático
# Campaña: summer2024 (019a8a60-dcc9-7372-95a7-2a68c2755456)
# Opción 1 configurada con WhatsApp

curl -X POST http://localhost:8000/api/webhooks/lead \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "2944636430",
    "name": "Juan Pérez",
    "city": "Bariloche",
    "campaign": "summer2024",
    "option_selected": "1",
    "source": "webhook_test",
    "notes": "Lead de prueba desde webhook"
  }' | jq .

echo ""
echo "✅ Webhook enviado!"
echo "El lead debería:"
echo "  1. Crearse con teléfono normalizado: +5492944636430"
echo "  2. Asociarse a campaña 'summer2024'"
echo "  3. Auto-procesar y enviar WhatsApp (opción 1)"
echo "  4. Registrar LeadIntent en estado PENDING"

