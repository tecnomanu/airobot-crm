<?php

namespace App\Models\Integration;

use App\Models\Campaign\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleIntegration extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'google_integration_id');
    }
}
