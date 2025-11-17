<?php

namespace App\Models;

use App\Enums\CallStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'phone',
        'campaign_id',
        'client_id',
        'call_date',
        'duration_seconds',
        'cost',
        'status',
        'lead_id',
        'provider',
        'call_id_external',
        'notes',
        'recording_url',
        'transcript',
        'created_by',
    ];

    protected $casts = [
        'call_date' => 'datetime',
        'duration_seconds' => 'integer',
        'cost' => 'decimal:4',
        'status' => CallStatus::class,
    ];

    /**
     * Relación con la campaña
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relación con el cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación con el lead (nullable)
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relación con el usuario que creó el registro
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
