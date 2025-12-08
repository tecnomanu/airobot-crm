<?php

namespace App\Models\Campaign;

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

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function isRetell(): bool
    {
        return $this->provider === CallAgentProvider::RETELL;
    }

    public function getFromNumber(): ?string
    {
        return $this->config['from_number'] ?? null;
    }

    public function getRetellAgentId(): ?string
    {
        return $this->config['agent_id'] ?? null;
    }

    public function getAgentVersion(): ?int
    {
        return $this->config['agent_version'] ?? null;
    }
}

