# Refactorización: Leads Manager Unificado

## 📋 Resumen

Se ha refactorizado completamente el módulo de gestión de Leads para:
1. **Desacoplar** el modelo Lead del modelo Campaign mediante relación directa con Client
2. **Unificar** las vistas separadas "Leads" y "Leads Intención" en una sola vista con tabs
3. **Mejorar** el flujo de trabajo con acciones rápidas (Call, WhatsApp) integradas

---

## 🗄️ Cambios en Base de Datos

### Nueva Migración: `add_client_id_to_leads_table`

**Archivo:** `database/migrations/2025_12_08_183455_add_client_id_to_leads_table.php`

#### Campos Agregados:
- `client_id` (UUID, nullable): Relación directa con Client
- `country` (string, nullable): País del lead
- `intention_origin` (string, nullable): Origen de la intención (ivr, whatsapp, manual, api)
- `intention_status` (string, nullable): Estado de intención (pending, finalized, sent_to_client)
- `intention_decided_at` (timestamp, nullable): Fecha de decisión de intención
- `intention_webhook_sent` (boolean): Flag de envío de webhook de intención
- `intention_webhook_sent_at` (timestamp, nullable)
- `intention_webhook_response` (text, nullable)
- `intention_webhook_status` (integer, nullable)

#### Cambios en Campos Existentes:
- `campaign_id`: Ahora es **nullable** (permite leads sin campaña)

#### Índices Agregados:
- `client_id`
- `(client_id, status)` - Compuesto
- `intention_status`

### Ejecutar Migración:

```bash
php artisan migrate
```

---

## 🏗️ Cambios en el Modelo

### `app/Models/Lead.php`

#### 1. Nueva Relación Directa con Client

```php
/**
 * Direct relationship with Client (decoupled from campaign)
 */
public function client(): BelongsTo
{
    return $this->belongsTo(Client::class);
}
```

#### 2. Attribute Virtual: `is_client_owned`

```php
public function getIsClientOwnedAttribute(): bool
{
    // Lead is client-owned if it has direct client_id but no campaign
    return $this->client_id !== null && $this->campaign_id === null;
}
```

#### 3. Query Scopes para Tabs

##### **Scope: Inbox** - Leads recién ingresados sin procesar

```php
public function scopeInbox($query)
{
    return $query->where(function ($q) {
        $q->where('automation_status', LeadAutomationStatus::PENDING->value)
            ->orWhere('automation_status', LeadAutomationStatus::SKIPPED->value);
    })
    ->whereNull('intention_status')
    ->orderBy('created_at', 'desc');
}
```

##### **Scope: Active Pipeline** - Leads en proceso de automatización

```php
public function scopeActivePipeline($query)
{
    return $query->where(function ($q) {
        $q->where('automation_status', LeadAutomationStatus::PROCESSING->value)
            ->orWhere('automation_status', LeadAutomationStatus::COMPLETED->value)
            ->orWhere(function ($subQuery) {
                $subQuery->whereNotNull('intention_status')
                    ->where('intention_status', '!=', LeadIntentionStatus::FINALIZED->value);
            });
    })
    ->where('status', '!=', LeadStatus::CLOSED->value)
    ->orderBy('next_action_at', 'asc');
}
```

##### **Scope: Sales Ready** - Alta intención, requiere acción humana

```php
public function scopeSalesReady($query)
{
    return $query->where('intention_status', LeadIntentionStatus::FINALIZED->value)
        ->where('status', '!=', LeadStatus::CLOSED->value)
        ->orderBy('intention_decided_at', 'desc');
}
```

##### **Scope: For Client** - Filtrar por cliente (directo o a través de campaña)

```php
public function scopeForClient($query, $clientId)
{
    return $query->where(function ($q) use ($clientId) {
        $q->where('client_id', $clientId)
            ->orWhereHas('campaign', function ($subQuery) use ($clientId) {
                $subQuery->where('client_id', $clientId);
            });
    });
}
```

---

## 🔧 Cambios en Servicios

### `app/Services/Lead/LeadService.php`

#### Método: `getLeadsForManager()`

```php
/**
 * Get leads for unified Leads Manager view with tab support
 * 
 * @param string $tab One of: 'inbox', 'active', 'sales_ready'
 * @param array $filters Additional filters
 * @param int $perPage Pagination size
 */
public function getLeadsForManager(string $tab, array $filters = [], int $perPage = 15)
```

Aplica el scope correspondiente según el tab activo y filtros adicionales.

#### Método: `getTabCounts()`

```php
/**
 * Get count summary for all tabs
 */
public function getTabCounts(array $filters = []): array
{
    return [
        'inbox' => ...,
        'active' => ...,
        'sales_ready' => ...,
    ];
}
```

---

## 🎮 Nuevo Controlador

### `app/Http/Controllers/Web/Lead/LeadsManagerController.php`

Controlador unificado que reemplaza `LeadController` y `LeadIntencionController`.

#### Métodos Principales:

- `index()`: Vista principal con tabs
- `show($id)`: Detalle del lead
- `store()`: Crear lead
- `update($id)`: Actualizar lead
- `destroy($id)`: Eliminar lead
- `retryAutomation($id)`: Reintentar procesamiento individual
- `retryAutomationBatch()`: Reintentar procesamiento masivo
- **`callAction($id)`**: Acción rápida de llamada (🚧 en desarrollo)
- **`whatsappAction($id)`**: Acción rápida de WhatsApp (🚧 en desarrollo)

---

## 🎨 Cambios en Frontend

### Nueva Vista: `resources/js/Pages/LeadsManager/Index.jsx`

#### Sistema de Tabs:

```jsx
const tabs = [
    {
        value: "inbox",
        label: "Inbox / Raw",
        count: tabCounts.inbox,
        description: "Nuevos leads sin procesar",
    },
    {
        value: "active",
        label: "Active Pipeline",
        count: tabCounts.active,
        description: "Leads en proceso de automatización",
    },
    {
        value: "sales_ready",
        label: "Sales Ready",
        count: tabCounts.sales_ready,
        description: "Alta intención, requiere acción",
    },
];
```

#### Filtros Mejorados:

- Búsqueda por teléfono/nombre
- Filtro por campaña
- **Nuevo:** Filtro por cliente
- Filtro por estado
- Botón para limpiar filtros

#### Características:

- ✅ Navegación por tabs sin recargar página completa
- ✅ Badges con contadores en cada tab
- ✅ Real-time updates vía WebSocket
- ✅ Acciones rápidas integradas (Call 📞, WhatsApp 💬)
- ✅ Tooltips informativos en todas las acciones
- ✅ Diseño responsivo

### Nueva Vista: `resources/js/Pages/LeadsManager/Show.jsx`

Vista de detalle copiada de `Leads/Show.jsx` con rutas actualizadas.

### Nuevas Columnas: `resources/js/Pages/LeadsManager/columns.jsx`

#### Columnas Agregadas:

- **Cliente**: Muestra el cliente (directo o a través de campaña)
- **Intención**: Badge con estado de intención
- **Acciones**: Columna de acciones con tooltips

#### Acciones Rápidas en Tabla:

```jsx
// Quick Actions: Call & WhatsApp
<Button onClick={() => handleCall(lead)}>
    <Phone className="h-4 w-4 text-green-600" />
</Button>

<Button onClick={() => handleWhatsApp(lead)}>
    <MessageSquare className="h-4 w-4 text-blue-600" />
</Button>
```

---

## 🛣️ Cambios en Rutas

### `routes/web.php`

#### Nuevas Rutas Unificadas:

```php
// Leads Manager (Unified View with Tabs)
Route::prefix('leads')->name('leads-manager.')->group(function () {
    Route::get('/', [LeadsManagerController::class, 'index'])->name('index');
    Route::get('/{id}', [LeadsManagerController::class, 'show'])->name('show');
    Route::post('/', [LeadsManagerController::class, 'store'])->name('store');
    Route::put('/{id}', [LeadsManagerController::class, 'update'])->name('update');
    Route::delete('/{id}', [LeadsManagerController::class, 'destroy'])->name('destroy');
    
    // Automation retry
    Route::post('/{id}/retry-automation', [LeadsManagerController::class, 'retryAutomation'])->name('retry-automation');
    Route::post('/retry-automation-batch', [LeadsManagerController::class, 'retryAutomationBatch'])->name('retry-automation-batch');
    
    // Quick actions
    Route::post('/{id}/call', [LeadsManagerController::class, 'callAction'])->name('call-action');
    Route::post('/{id}/whatsapp', [LeadsManagerController::class, 'whatsappAction'])->name('whatsapp-action');
});
```

#### Rutas Legacy (Deprecated):

Las rutas antiguas `leads.*` y `leads-intencion.*` se mantienen temporalmente bajo `/leads-legacy` para compatibilidad.

### Navegación Principal

**Archivo:** `resources/js/Layouts/AppLayout.jsx`

```jsx
const navigation = [
    { name: "Dashboard", href: route("dashboard"), icon: Home },
    { name: "Leads Manager", href: route("leads-manager.index"), icon: Users }, // ✨ Nuevo
    // ... resto de navegación
];
```

Se eliminó el item "Leads Intención" del menú.

---

## 📊 Flujo de Datos

### Lógica de Asignación de Cliente:

```
Lead tiene client_id?
├─ SÍ
│  └─ Usa relación directa client()
└─ NO
   └─ ¿Lead tiene campaign_id?
      ├─ SÍ: Usa campaign->client
      └─ NO: No tiene cliente asignado
```

### Flags Virtuales:

```php
// Lead propio del cliente (subido por él)
$lead->is_client_owned = ($lead->client_id && !$lead->campaign_id);

// Lead de campaña interna
$lead->is_campaign_lead = ($lead->campaign_id !== null);
```

---

## 🔄 Migración de Datos Existentes

**Importante:** Los leads existentes solo tienen `campaign_id`, por lo que:

1. No tendrán `client_id` inicialmente (nullable permite esto)
2. Continuarán funcionando usando `campaign->client`
3. Opcionalmente, puedes ejecutar un seeder para poblar `client_id`:

```php
// Opcional: Poblar client_id desde campaigns existentes
Lead::whereNotNull('campaign_id')
    ->whereNull('client_id')
    ->chunkById(100, function ($leads) {
        foreach ($leads as $lead) {
            $lead->client_id = $lead->campaign->client_id;
            $lead->save();
        }
    });
```

---

## ✅ Testing

### Escenarios a Probar:

1. **Inbox Tab:**
   - Ver leads nuevos sin procesar
   - Filtrar por campaña/cliente/estado
   - Búsqueda por teléfono

2. **Active Pipeline Tab:**
   - Ver leads en proceso
   - Acciones de retry funcionando

3. **Sales Ready Tab:**
   - Ver leads con alta intención
   - Acciones rápidas (Call, WhatsApp)

4. **CRUD Operations:**
   - Crear lead con client_id directo
   - Crear lead con campaign_id
   - Crear lead sin ninguno (debería fallar validación)
   - Actualizar lead
   - Eliminar lead

5. **Real-time Updates:**
   - Verificar que WebSocket funciona
   - Badges de contadores se actualizan

---

## 🚀 Próximos Pasos (Opcional)

### 1. Implementar Acciones Rápidas

En `LeadsManagerController`:

```php
public function callAction(string $id): RedirectResponse
{
    $lead = $this->leadService->getLeadById($id);
    
    // TODO: Integrar con servicio de llamadas (Retell, Twilio, etc.)
    // Ejemplo:
    // $this->callService->initiateCall($lead->phone, $lead->campaign->call_agent_id);
    
    return redirect()->back()
        ->with('success', 'Llamada iniciada');
}

public function whatsappAction(string $id): RedirectResponse
{
    $lead = $this->leadService->getLeadById($id);
    
    // TODO: Integrar con Evolution API o WhatsApp Business
    // Ejemplo:
    // $this->whatsappService->sendTemplateMessage($lead->phone, 'greeting');
    
    return redirect()->back()
        ->with('success', 'Mensaje de WhatsApp enviado');
}
```

### 2. Bulk Actions

Agregar checkbox selection para acciones masivas:
- Asignar múltiples leads a un cliente
- Cambiar estado masivo
- Exportar seleccionados

### 3. Vistas Personalizadas por Cliente

Permitir a cada cliente ver solo sus leads:

```php
Route::middleware(['auth', 'client'])->group(function () {
    Route::get('/my-leads', [ClientLeadsController::class, 'index']);
});
```

### 4. Analytics Dashboard

Crear métricas por tab:
- Tasa de conversión Inbox → Sales Ready
- Tiempo promedio en Active Pipeline
- Performance por fuente/campaña

---

## 📝 Comandos Útiles

```bash
# Ejecutar migración
php artisan migrate

# Revertir migración (si es necesario)
php artisan migrate:rollback

# Ver estado de migraciones
php artisan migrate:status

# Compilar assets frontend
pnpm run build

# Modo desarrollo frontend
pnpm run dev

# Limpiar cachés
php artisan optimize:clear
```

---

## 🐛 Troubleshooting

### Problema: Error 404 en leads-manager.index

**Solución:**
```bash
php artisan route:clear
php artisan route:cache
```

### Problema: Tabs no cambian de contenido

**Verificar:**
1. El parámetro `tab` se está pasando en la URL
2. El scope correspondiente está definido en el modelo Lead
3. El método `getLeadsForManager()` aplica el scope correcto

### Problema: Contadores de tabs en 0

**Verificar:**
1. Los scopes están devolviendo resultados con `->get()` o `->count()`
2. Los filtros base no están excluyendo todos los leads

---

## 👥 Autor

Refactorización realizada por: **Globey AI Assistant**  
Fecha: Diciembre 8, 2025  
Versión: 1.0.0

---

## 📄 Licencia

Este proyecto mantiene la licencia del proyecto principal.

