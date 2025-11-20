<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\ExportRule;
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
        'country',
        'campaign_type',
        'export_rule',
        'intention_interested_webhook_id',
        'intention_not_interested_webhook_id',
        'send_intention_interested_webhook',
        'send_intention_not_interested_webhook',
        'created_by',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'campaign_type' => CampaignType::class,
        'export_rule' => ExportRule::class,
        'auto_process_enabled' => 'boolean',
        'send_intention_interested_webhook' => 'boolean',
        'send_intention_not_interested_webhook' => 'boolean',
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
     * Relación con el webhook de intención para leads interesados
     */
    public function intentionInterestedWebhook(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'intention_interested_webhook_id');
    }

    /**
     * Relación con el webhook de intención para leads no interesados
     */
    public function intentionNotInterestedWebhook(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'intention_not_interested_webhook_id');
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

    /**
     * Obtener el agente de llamadas habilitado
     */
    public function getEnabledCallAgent(): ?CampaignCallAgent
    {
        return $this->callAgent()->where('enabled', true)->first();
    }

    /**
     * Verificar si tiene un agente de Retell configurado y habilitado
     */
    public function hasRetellCallAgent(): bool
    {
        $agent = $this->getEnabledCallAgent();

        return $agent && $agent->isRetell();
    }
}
