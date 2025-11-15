<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Campaign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'client_id',
        'description',
        'status',
        'slug',
        'auto_process_enabled',
        'created_by',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'auto_process_enabled' => 'boolean',
    ];

    /**
     * Relación con el cliente al que pertenece la campaña
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación con el usuario que creó la campaña
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con los leads de la campaña
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Relación con el historial de llamadas de la campaña
     */
    public function callHistories(): HasMany
    {
        return $this->hasMany(CallHistory::class);
    }

    /**
     * Relación con los templates de WhatsApp
     */
    public function whatsappTemplates(): HasMany
    {
        return $this->hasMany(CampaignWhatsappTemplate::class);
    }

    /**
     * Relación con el agente de llamadas
     */
    public function callAgent(): HasOne
    {
        return $this->hasOne(CampaignCallAgent::class);
    }

    /**
     * Relación con el agente de WhatsApp
     */
    public function whatsappAgent(): HasOne
    {
        return $this->hasOne(CampaignWhatsappAgent::class);
    }

    /**
     * Relación con las opciones de la campaña
     */
    public function options(): HasMany
    {
        return $this->hasMany(CampaignOption::class);
    }

    /**
     * Obtener opción específica por clave
     */
    public function getOption(string $optionKey): ?CampaignOption
    {
        return $this->options()->where('option_key', $optionKey)->first();
    }

    /**
     * Verificar si tiene un agente de llamadas habilitado
     */
    public function hasCallAgent(): bool
    {
        return $this->callAgent()->where('enabled', true)->exists();
    }

    /**
     * Verificar si tiene un agente de WhatsApp habilitado
     */
    public function hasWhatsappAgent(): bool
    {
        return $this->whatsappAgent()->where('enabled', true)->exists();
    }
}
