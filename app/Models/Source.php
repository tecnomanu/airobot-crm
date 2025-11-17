<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa una fuente/conector externo (WhatsApp, Webhook, etc.)
 *
 * @property int $id
 * @property string $name
 * @property SourceType $type
 * @property array $config
 * @property SourceStatus $status
 * @property int|null $client_id
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Client|null $client
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|Campaign[] $campaignsAsWhatsapp
 * @property-read \Illuminate\Database\Eloquent\Collection|Campaign[] $campaignsAsWebhook
 */
class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'config',
        'status',
        'client_id',
        'created_by',
    ];

    protected $casts = [
        'type' => SourceType::class,
        'status' => SourceStatus::class,
        'config' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el cliente propietario
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación con el usuario que creó la fuente
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con las opciones de campaña que usan esta fuente
     */
    public function campaignOptions(): HasMany
    {
        return $this->hasMany(CampaignOption::class, 'source_id');
    }

    /**
     * Obtiene todas las campañas asociadas a través de campaign_options
     */
    public function campaigns()
    {
        return Campaign::whereHas('options', function ($query) {
            $query->where('source_id', $this->id);
        })->get();
    }

    // ========== SCOPES ==========

    /**
     * Filtrar fuentes por tipo
     */
    public function scopeOfType($query, SourceType|string $type)
    {
        $typeValue = $type instanceof SourceType ? $type->value : $type;

        return $query->where('type', $typeValue);
    }

    /**
     * Filtrar fuentes de WhatsApp
     */
    public function scopeWhatsapp($query)
    {
        return $query->where('type', SourceType::WHATSAPP->value);
    }

    /**
     * Filtrar fuentes de Webhook
     */
    public function scopeWebhook($query)
    {
        return $query->where('type', SourceType::WEBHOOK->value);
    }

    /**
     * Filtrar fuentes de Meta WhatsApp
     */
    public function scopeMetaWhatsapp($query)
    {
        return $query->where('type', SourceType::META_WHATSAPP->value);
    }

    /**
     * Filtrar fuentes activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', SourceStatus::ACTIVE->value);
    }

    /**
     * Filtrar fuentes inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('status', SourceStatus::INACTIVE->value);
    }

    /**
     * Filtrar fuentes con error
     */
    public function scopeWithError($query)
    {
        return $query->where('status', SourceStatus::ERROR->value);
    }

    /**
     * Filtrar fuentes por cliente
     */
    public function scopeForClient($query, string|int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // ========== HELPERS ==========

    /**
     * Verifica si la fuente está activa
     */
    public function isActive(): bool
    {
        return $this->status === SourceStatus::ACTIVE;
    }

    /**
     * Verifica si la fuente es de mensajería
     */
    public function isMessaging(): bool
    {
        return $this->type->isMessaging();
    }

    /**
     * Verifica si la fuente es de publicidad
     */
    public function isAdvertising(): bool
    {
        return $this->type->isAdvertising();
    }

    /**
     * Obtiene un valor específico de la configuración
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Verifica si tiene todos los campos requeridos en config
     */
    public function hasValidConfig(): bool
    {
        $required = $this->type->requiredConfigFields();

        foreach ($required as $field) {
            if (empty($this->getConfigValue($field))) {
                return false;
            }
        }

        return true;
    }
}
