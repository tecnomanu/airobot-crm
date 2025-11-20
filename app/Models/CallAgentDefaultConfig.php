<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallAgentDefaultConfig extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Obtener la configuración activa por defecto
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
