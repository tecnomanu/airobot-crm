<?php

declare(strict_types=1);

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks the round-robin cursor position for lead assignment per campaign.
 */
class CampaignAssignmentCursor extends Model
{
    use HasUuids;

    protected $fillable = [
        'campaign_id',
        'current_index',
        'last_assigned_at',
    ];

    protected $casts = [
        'current_index' => 'integer',
        'last_assigned_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Advance the cursor to the next position.
     */
    public function advance(int $totalAssignees): void
    {
        $this->current_index = ($this->current_index + 1) % $totalAssignees;
        $this->last_assigned_at = now();
        $this->save();
    }

    /**
     * Reset cursor to beginning.
     */
    public function reset(): void
    {
        $this->current_index = 0;
        $this->save();
    }
}

