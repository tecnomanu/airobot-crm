<?php

namespace App\Models\Integration;

use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleIntegration extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'created_by_user_id',
        'google_id',
        'email',
        'name',
        'avatar',
        'access_token',
        'refresh_token',
        'expires_in',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'expires_in' => 'integer',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Client (tenant) that owns this integration.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * User who created/connected this integration (audit trail).
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @deprecated Use createdBy() instead. Kept for backwards compatibility.
     */
    public function user(): BelongsTo
    {
        return $this->createdBy();
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'google_integration_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter integrations by client (tenant scoping).
     */
    public function scopeForClient(Builder $query, string $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for integrations visible to a given user.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        // User sees integrations from their own client only
        if ($user->client_id) {
            return $query->where('client_id', $user->client_id);
        }

        // Global users without client see nothing (edge case, should not happen)
        return $query->whereRaw('1 = 0');
    }
}
