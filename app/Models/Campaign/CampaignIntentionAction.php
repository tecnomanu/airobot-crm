<?php

namespace App\Models\Campaign;

use App\Models\Integration\GoogleIntegration;
use App\Models\Integration\Source;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignIntentionAction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'intention_type',
        'action_type',
        'webhook_id',
        'google_integration_id',
        'google_spreadsheet_id',
        'google_sheet_name',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'webhook_id');
    }

    public function googleIntegration(): BelongsTo
    {
        return $this->belongsTo(GoogleIntegration::class);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if this is an interested intention action
     */
    public function isInterestedAction(): bool
    {
        return $this->intention_type === 'interested';
    }

    /**
     * Check if this is a not interested intention action
     */
    public function isNotInterestedAction(): bool
    {
        return $this->intention_type === 'not_interested';
    }

    /**
     * Check if action type is webhook
     */
    public function isWebhook(): bool
    {
        return $this->action_type === 'webhook';
    }

    /**
     * Check if action type is spreadsheet
     */
    public function isSpreadsheet(): bool
    {
        return $this->action_type === 'spreadsheet';
    }

    /**
     * Check if this action is properly configured
     */
    public function isConfigured(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->isWebhook()) {
            return !empty($this->webhook_id);
        }

        if ($this->isSpreadsheet()) {
            return !empty($this->google_integration_id)
                && !empty($this->google_spreadsheet_id)
                && !empty($this->google_sheet_name);
        }

        return false;
    }

    /**
     * Get a human-readable description of this action
     */
    public function getDescription(): string
    {
        if (!$this->enabled) {
            return 'Deshabilitado';
        }

        $intentionLabel = $this->isInterestedAction() ? 'Interesado' : 'No Interesado';

        if ($this->isWebhook() && $this->webhook) {
            return "{$intentionLabel} → Webhook: {$this->webhook->name}";
        }

        if ($this->isSpreadsheet()) {
            return "{$intentionLabel} → Google Sheet: {$this->google_sheet_name}";
        }

        return "{$intentionLabel} → Sin configurar";
    }
}
