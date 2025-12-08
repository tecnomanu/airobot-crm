<?php

declare(strict_types=1);

namespace App\Models\Integration;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignOption;
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa una fuente/conector externo (WhatsApp, Webhook, etc.)
 */
class Source extends Model
{
    use HasFactory, HasUuids;

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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaignOptions(): HasMany
    {
        return $this->hasMany(CampaignOption::class, 'source_id');
    }

    public function campaigns()
    {
        return Campaign::whereHas('options', function ($query) {
            $query->where('source_id', $this->id);
        })->get();
    }

    public function scopeOfType($query, SourceType|string $type)
    {
        $typeValue = $type instanceof SourceType ? $type->value : $type;
        return $query->where('type', $typeValue);
    }

    public function scopeWhatsapp($query)
    {
        return $query->where('type', SourceType::WHATSAPP->value);
    }

    public function scopeWebhook($query)
    {
        return $query->where('type', SourceType::WEBHOOK->value);
    }

    public function scopeMetaWhatsapp($query)
    {
        return $query->where('type', SourceType::META_WHATSAPP->value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SourceStatus::ACTIVE->value);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', SourceStatus::INACTIVE->value);
    }

    public function scopeWithError($query)
    {
        return $query->where('status', SourceStatus::ERROR->value);
    }

    public function scopeForClient($query, string|int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function isActive(): bool
    {
        return $this->status === SourceStatus::ACTIVE;
    }

    public function isMessaging(): bool
    {
        return $this->type->isMessaging();
    }

    public function isAdvertising(): bool
    {
        return $this->type->isAdvertising();
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

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

