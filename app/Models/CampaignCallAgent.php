<?php

namespace App\Models;

use App\Enums\CallAgentProvider;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignCallAgent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'name',
        'provider',
        'config',
        'enabled',
    ];

    protected $casts = [
        'config' => 'array',
        'enabled' => 'boolean',
        'provider' => CallAgentProvider::class,
    ];

    /**
     * Relación con la campaña
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Verificar si el agente está configurado para Retell
     */
    public function isRetell(): bool
    {
        return $this->provider === \App\Enums\CallAgentProvider::RETELL;
    }

    /**
     * Obtener el número de origen configurado
     */
    public function getFromNumber(): ?string
    {
        return $this->config['from_number'] ?? null;
    }

    /**
     * Obtener el ID del agente de Retell configurado
     */
    public function getRetellAgentId(): ?string
    {
        return $this->config['agent_id'] ?? null;
    }

    /**
     * Obtener la versión del agente configurada
     */
    public function getAgentVersion(): ?int
    {
        return $this->config['agent_version'] ?? null;
    }
}
