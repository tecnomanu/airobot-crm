<?php

namespace App\Models\Client;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Models\Campaign\Campaign;
use App\Models\Integration\GoogleIntegration;
use App\Models\Integration\Source;
use App\Models\Lead\LeadActivity;
use App\Models\Lead\LeadCall;
use App\Models\RetellAgent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasUuids;

    /**
     * Well-known UUID for the internal AirRobot HQ client.
     */
    public const INTERNAL_CLIENT_ID = '00000000-0000-0000-0000-000000000001';

    protected static function newFactory()
    {
        return \Database\Factories\ClientFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'billing_info',
        'status',
        'type',
        'parent_client_id',
        'notes',
        'created_by',
        'default_call_agent_id',
        'default_whatsapp_source_id',
    ];

    protected $casts = [
        'billing_info' => 'array',
        'status' => ClientStatus::class,
        'type' => ClientType::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Parent client (for reseller/franchise hierarchy).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'parent_client_id');
    }

    /**
     * Child clients (sub-clients for resellers/franchises).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Client::class, 'parent_client_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Google integrations owned by this client.
     */
    public function googleIntegrations(): HasMany
    {
        return $this->hasMany(GoogleIntegration::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(LeadCall::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    // =========================================================================
    // DEFAULT AGENT RELATIONSHIPS
    // =========================================================================

    /**
     * Default Retell call agent for this client.
     */
    public function defaultCallAgent(): BelongsTo
    {
        return $this->belongsTo(RetellAgent::class, 'default_call_agent_id');
    }

    /**
     * Default WhatsApp source for this client.
     */
    public function defaultWhatsappSource(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'default_whatsapp_source_id');
    }

    // =========================================================================
    // SELLERS (Users marked as sellers for this client)
    // =========================================================================

    /**
     * Get users marked as sellers for this client.
     */
    public function sellers(): HasMany
    {
        return $this->hasMany(User::class, 'client_id')
            ->where('is_seller', true)
            ->orderBy('name');
    }

    /**
     * Get the owner/creator of this client (fallback for assignment).
     */
    public function getOwner(): ?User
    {
        return $this->creator;
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Check if this is the internal AirRobot HQ client.
     */
    public function isInternal(): bool
    {
        return $this->type === ClientType::INTERNAL;
    }

    /**
     * Check if this client can have sub-clients (reseller/franchise).
     */
    public function canHaveSubClients(): bool
    {
        return $this->type->canHaveSubClients();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to exclude internal client from listings.
     */
    public function scopeExcludeInternal(Builder $query): Builder
    {
        return $query->where('type', '!=', ClientType::INTERNAL->value);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType(Builder $query, ClientType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    // =========================================================================
    // STATIC HELPERS
    // =========================================================================

    /**
     * Get the internal AirRobot HQ client.
     */
    public static function internal(): ?self
    {
        return static::find(self::INTERNAL_CLIENT_ID);
    }
}

