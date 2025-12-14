<?php

namespace App\Models\Client;

use App\Enums\ClientStatus;
use App\Models\Campaign\Campaign;
use App\Models\Lead\LeadActivity;
use App\Models\Lead\LeadCall;
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
}

