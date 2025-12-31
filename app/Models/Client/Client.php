<?php

namespace App\Models\Client;

use App\Enums\ClientStatus;
use App\Models\Campaign\Campaign;
use App\Models\Integration\Source;
use App\Models\Lead\LeadActivity;
use App\Models\Lead\LeadCall;
use App\Models\RetellAgent;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasUuids;

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
        'notes',
        'created_by',
        'default_call_agent_id',
        'default_whatsapp_source_id',
    ];

    protected $casts = [
        'billing_info' => 'array',
        'status' => ClientStatus::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
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
}

