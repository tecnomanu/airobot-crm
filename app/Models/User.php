<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\Integration\GoogleIntegration;
use App\Models\Lead\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_seller',
        'status',
        'client_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'is_seller' => 'boolean',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Client this user belongs to (null for global/admin users).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Clients created by this user.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    /**
     * Campaigns created by this user.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    /**
     * Leads created by this user.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'created_by');
    }

    /**
     * Leads assigned to this user (as seller).
     */
    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    /**
     * Google integrations created by this user (audit trail).
     */
    public function createdGoogleIntegrations(): HasMany
    {
        return $this->hasMany(GoogleIntegration::class, 'created_by_user_id');
    }

    // =========================================================================
    // SIMPLE ACCESSORS (no business logic, just data transformation)
    // =========================================================================

    /**
     * Check if user has no client assigned (global user).
     */
    public function isGlobalUser(): bool
    {
        return $this->client_id === null;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is a supervisor.
     */
    public function isSupervisor(): bool
    {
        return $this->role === UserRole::SUPERVISOR;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter users by client.
     */
    public function scopeForClient(Builder $query, ?string $clientId): Builder
    {
        if ($clientId) {
            return $query->where('client_id', $clientId);
        }

        return $query;
    }

    /**
     * Scope to filter only sellers.
     */
    public function scopeSellers(Builder $query): Builder
    {
        return $query->where('is_seller', true);
    }

    /**
     * Scope to filter by role.
     */
    public function scopeWithRole(Builder $query, UserRole|string $role): Builder
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        return $query->where('role', $roleValue);
    }

    /**
     * Scope to filter active users only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::ACTIVE->value);
    }

    /**
     * Scope for users visible to a given user (used by repository).
     */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isAdmin()) {
            return $query;
        }

        if ($viewer->isSupervisor()) {
            if ($viewer->client_id) {
                return $query->where('client_id', $viewer->client_id);
            }

            return $query->where('role', '!=', UserRole::ADMIN->value);
        }

        return $query->where('id', $viewer->id);
    }
}
