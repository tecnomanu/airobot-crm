#!/bin/bash

echo "═══════════════════════════════════════════════════════════"
echo "   📱 MONITOR DE WEBHOOKS WHATSAPP - AIROBOT"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "🔍 Monitoreando logs en tiempo real..."
echo "📝 Envía un mensaje desde WhatsApp al número 2944636430"
echo ""
echo "───────────────────────────────────────────────────────────"
echo ""

tail -f storage/logs/laravel.log | grep --line-buffered -E "(DEBUG.*teléfono|Payload completo|remoteJid|phone_normalizado|Procesando mensaje|Lead.*encontrado)" --color=always

