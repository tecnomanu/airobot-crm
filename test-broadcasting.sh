#!/bin/bash

echo "🔧 Test de Broadcasting - Laravel Reverb"
echo "=========================================="
echo ""

# 1. Verificar que Reverb esté corriendo
echo "1️⃣ Verificando Reverb..."
REVERB_STATUS=$(docker ps --filter "name=reverb" --format "{{.Status}}")
if [[ $REVERB_STATUS == *"Up"* ]]; then
    echo "   ✅ Reverb está corriendo: $REVERB_STATUS"
else
    echo "   ❌ Reverb NO está corriendo"
    exit 1
fi

echo ""

# 2. Verificar configuración
echo "2️⃣ Verificando configuración..."
BROADCAST_CONN=$(docker exec airobot-airobot.laravel-1 php artisan tinker --execute="echo config('broadcasting.default');")
echo "   BROADCAST_CONNECTION: $BROADCAST_CONN"

if [[ "$BROADCAST_CONN" == "reverb" ]]; then
    echo "   ✅ Broadcasting configurado correctamente"
else
    echo "   ❌ Broadcasting NO está configurado para Reverb"
fi

echo ""

# 3. Crear un lead de prueba
echo "3️⃣ Creando lead de prueba..."
docker exec airobot-airobot.laravel-1 php artisan tinker --execute="
\$campaign = App\Models\Campaign::first();
if (!\$campaign) {
    echo '❌ No hay campañas en la base de datos';
    exit(1);
}

\$lead = App\Models\Lead::create([
    'phone' => '+34' . rand(600000000, 699999999),
    'name' => 'Test Broadcasting ' . date('H:i:s'),
    'campaign_id' => \$campaign->id,
    'status' => 'pending',
    'source' => 'test',
]);

echo '✅ Lead creado: ' . \$lead->id;

// Emitir evento manualmente
broadcast(new App\Events\LeadUpdated(\$lead->load('campaign'), 'created'));
echo ' - Evento emitido';
"

echo ""

# 4. Ver logs de Reverb
echo "4️⃣ Últimos logs de Reverb (deberías ver el evento):"
echo "---------------------------------------------------"
docker logs airobot-reverb-1 --tail=20 2>&1

echo ""
echo "=========================================="
echo "✅ Test completado"
echo ""
echo "Ahora ve a la consola del navegador y deberías ver:"
echo "   - 'Evento de lead recibido: ...'"
echo "   - Notificación nativa"
echo "   - Toast verde"

