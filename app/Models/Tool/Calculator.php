<?php

declare(strict_types=1);

namespace App\Models\Tool;

use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calculator extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'excels';

    protected $fillable = [
        'user_id',
        'client_id',
        'name',
        'data',
        'last_cursor_position',
        'column_widths',
        'row_heights',
        'frozen_rows',
        'frozen_columns',
        'version',
    ];

    protected $casts = [
        'data' => 'array',
        'last_cursor_position' => 'array',
        'column_widths' => 'array',
        'row_heights' => 'array',
        'frozen_rows' => 'integer',
        'frozen_columns' => 'integer',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getCell(string $cellId): ?array
    {
        return $this->data[$cellId] ?? null;
    }

    public function setCell(string $cellId, array $cellData): void
    {
        $data = $this->data ?? [];
        $data[$cellId] = $cellData;
        $this->data = $data;
    }

    public function removeCell(string $cellId): void
    {
        $data = $this->data ?? [];
        unset($data[$cellId]);
        $this->data = $data;
    }

    public function getColumnWidth(string $column): ?int
    {
        return $this->column_widths[$column] ?? null;
    }

    public function setColumnWidth(string $column, int $width): void
    {
        $widths = $this->column_widths ?? [];
        $widths[$column] = $width;
        $this->column_widths = $widths;
    }

    public function getRowHeight(int $row): ?int
    {
        return $this->row_heights[$row] ?? null;
    }

    public function setRowHeight(int $row, int $height): void
    {
        $heights = $this->row_heights ?? [];
        $heights[$row] = $height;
        $this->row_heights = $heights;
    }
}
