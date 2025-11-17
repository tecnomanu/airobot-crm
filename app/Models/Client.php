<?php

namespace App\Models;

use App\Enums\ClientStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasUuids;

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

    /**
     * Relación con el usuario que creó el cliente
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con las campañas del cliente
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Relación con el historial de llamadas del cliente
     */
    public function callHistories(): HasMany
    {
        return $this->hasMany(CallHistory::class);
    }
}
