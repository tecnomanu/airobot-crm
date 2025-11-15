# Debugging: Campaña no guarda cambios

## Problema Actual

-   URL: http://localhost:8001/campaigns/019a7cf7-529b-73a8-affa-af582f4ad7cf
-   Los cambios no se guardan en la base de datos
-   Se redirige a campaigns.index después de guardar

## Cambios Realizados

### 1. CampaignController - Cambio de redirect ✅

**Archivo**: `app/Http/Controllers/Web/Campaign/CampaignController.php`

**Antes**:

```php
return redirect()->route('campaigns.index')
    ->with('success', 'Campaña actualizada exitosamente');
```

**Después**:

```php
return redirect()->back()
    ->with('success', 'Campaña actualizada exitosamente');
```

### 2. Frontend - Opciones de Inertia ✅

**Archivo**: `resources/js/Pages/Campaigns/Show.jsx`

```jsx
put(route("campaigns.update", campaign.id), {
    preserveScroll: true,
    preserveState: true,
    only: ["campaign", "errors"],
    onSuccess: () => {
        toast.success("Campaña actualizada exitosamente");
    },
});
```

## Verificación Necesaria

### 1. Verificar que los datos llegan al controlador

Agregar temporalmente en `CampaignController@update`:

```php
\Log::info('Campaign Update Request', $request->validated());
```

### 2. Verificar que el repositorio guarda

En `CampaignRepository`:

```php
public function update(Campaign $campaign, array $data): Campaign
{
    \Log::info('Updating campaign', ['id' => $campaign->id, 'data' => $data]);
    $campaign->update($data);
    return $campaign->fresh();
}
```

### 3. Verificar en la base de datos

```bash
php artisan tinker
Campaign::find('019a7cf7-529b-73a8-affa-af582f4ad7cf')
```

## Pasos para Debugging

1. Limpiar cache ✅
2. Probar guardar desde el navegador
3. Revisar logs: `storage/logs/laravel.log`
4. Verificar la tabla campaigns en la base de datos directamente

## Posibles Causas

1. ❌ El repositorio no está guardando
2. ❌ La validación está fallando silenciosamente
3. ❌ Los campos no están en el fillable del modelo
4. ❌ El useForm de Inertia no está enviando los datos correctamente
