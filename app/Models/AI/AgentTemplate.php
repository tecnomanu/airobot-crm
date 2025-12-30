<?php

namespace App\Models\AI;

use App\Enums\AgentTemplateType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'description',
        'style_section',
        'behavior_section',
        'data_section_template',
        'available_variables',
        'retell_config_template',
        'is_active',
    ];

    protected $casts = [
        'type' => AgentTemplateType::class,
        'available_variables' => 'array',
        'retell_config_template' => 'array',
        'is_active' => 'boolean',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function campaignAgents(): HasMany
    {
        return $this->hasMany(CampaignAgent::class);
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, AgentTemplateType $type)
    {
        return $query->where('type', $type);
    }

    // =========================================================================
    // ACCESSORS & HELPERS
    // =========================================================================

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getAvailableVariables(): array
    {
        return $this->available_variables ?? [];
    }

    public function hasVariable(string $variableName): bool
    {
        return in_array($variableName, $this->getAvailableVariables());
    }

    /**
     * Get the default Retell configuration template
     */
    public function getRetellConfigTemplate(): array
    {
        return $this->retell_config_template ?? [];
    }

    /**
     * Check if this template has any active campaign agents
     */
    public function hasActiveCampaignAgents(): bool
    {
        return $this->campaignAgents()->where('enabled', true)->exists();
    }
}
