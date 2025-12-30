<?php

namespace App\Models\AI;

use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignCallAgent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CampaignAgent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'agent_template_id',
        'name',
        'intention_prompt',
        'variables',
        'flow_section',
        'final_prompt',
        'retell_agent_id',
        'retell_config',
        'is_synced',
        'last_synced_at',
        'enabled',
    ];

    protected $casts = [
        'variables' => 'array',
        'retell_config' => 'array',
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
        'enabled' => 'boolean',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AgentTemplate::class, 'agent_template_id');
    }

    /**
     * Relación con la capa técnica de configuración de llamadas
     * (opcional, si se necesita integrar con CampaignCallAgent existente)
     */
    public function callAgent(): HasOne
    {
        return $this->hasOne(CampaignCallAgent::class, 'campaign_id', 'campaign_id');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeSynced($query)
    {
        return $query->where('is_synced', true);
    }

    public function scopeNotSynced($query)
    {
        return $query->where('is_synced', false);
    }

    // =========================================================================
    // STATE CHECKERS
    // =========================================================================

    public function isSynced(): bool
    {
        return $this->is_synced && !empty($this->retell_agent_id);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function hasRetellAgent(): bool
    {
        return !empty($this->retell_agent_id);
    }

    /**
     * Check if the agent needs regeneration based on changes
     */
    public function needsRegeneration(): bool
    {
        // Si no tiene flow_section o final_prompt, necesita generación
        if (empty($this->flow_section) || empty($this->final_prompt)) {
            return true;
        }

        // Si el template fue actualizado después de la última generación
        if ($this->template && $this->template->updated_at > $this->updated_at) {
            return true;
        }

        return false;
    }

    /**
     * Check if the agent needs re-sync with Retell
     */
    public function needsSync(): bool
    {
        // Si no está sincronizado
        if (!$this->is_synced) {
            return true;
        }

        // Si no tiene ID de Retell
        if (empty($this->retell_agent_id)) {
            return true;
        }

        // Si el prompt cambió después de la última sincronización
        if ($this->last_synced_at && $this->updated_at > $this->last_synced_at) {
            return true;
        }

        return false;
    }

    // =========================================================================
    // VARIABLE HELPERS
    // =========================================================================

    public function getVariables(): array
    {
        return $this->variables ?? [];
    }

    public function getVariable(string $key, $default = null)
    {
        return $this->variables[$key] ?? $default;
    }

    public function setVariable(string $key, $value): void
    {
        $variables = $this->variables ?? [];
        $variables[$key] = $value;
        $this->variables = $variables;
    }

    /**
     * Replace variables in a template string
     *
     * @param string $template Template string with {{variable}} placeholders
     * @return string String with variables replaced
     */
    public function replaceVariables(string $template): string
    {
        $variables = $this->getVariables();

        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($variables) {
            $key = $matches[1];
            return $variables[$key] ?? $matches[0]; // Keep placeholder si no existe la variable
        }, $template);
    }

    // =========================================================================
    // PROMPT COMPOSITION
    // =========================================================================

    /**
     * Get the composed final prompt (from cache or regenerate marker)
     */
    public function getFinalPrompt(): ?string
    {
        return $this->final_prompt;
    }

    /**
     * Check if has a cached final prompt
     */
    public function hasFinalPrompt(): bool
    {
        return !empty($this->final_prompt);
    }

    /**
     * Clear the cached final prompt (forces regeneration)
     */
    public function clearFinalPrompt(): void
    {
        $this->final_prompt = null;
        $this->flow_section = null;
        $this->is_synced = false;
    }

    // =========================================================================
    // RETELL CONFIG
    // =========================================================================

    public function getRetellConfig(): array
    {
        return $this->retell_config ?? [];
    }

    public function setRetellConfig(array $config): void
    {
        $this->retell_config = $config;
    }

    public function mergeRetellConfig(array $config): void
    {
        $current = $this->getRetellConfig();
        $this->retell_config = array_merge($current, $config);
    }
}
