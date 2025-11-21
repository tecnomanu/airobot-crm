<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calculator extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'excels'; // Mantener nombre de tabla interno

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
    ];

    protected $casts = [
        'data' => 'array',
        'last_cursor_position' => 'array',
        'column_widths' => 'array',
        'row_heights' => 'array',
        'frozen_rows' => 'integer',
        'frozen_columns' => 'integer',
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

    /**
     * Obtener una celda específica
     */
    public function getCell(string $cellId): ?array
    {
        return $this->data[$cellId] ?? null;
    }

    /**
     * Establecer una celda
     */
    public function setCell(string $cellId, array $cellData): void
    {
        $data = $this->data ?? [];
        $data[$cellId] = $cellData;
        $this->data = $data;
    }

    /**
     * Eliminar una celda
     */
    public function removeCell(string $cellId): void
    {
        $data = $this->data ?? [];
        unset($data[$cellId]);
        $this->data = $data;
    }

    /**
     * Obtener ancho de columna
     */
    public function getColumnWidth(string $column): ?int
    {
        return $this->column_widths[$column] ?? null;
    }

    /**
     * Establecer ancho de columna
     */
    public function setColumnWidth(string $column, int $width): void
    {
        $widths = $this->column_widths ?? [];
        $widths[$column] = $width;
        $this->column_widths = $widths;
    }

    /**
     * Obtener altura de fila
     */
    public function getRowHeight(int $row): ?int
    {
        return $this->row_heights[$row] ?? null;
    }

    /**
     * Establecer altura de fila
     */
    public function setRowHeight(int $row, int $height): void
    {
        $heights = $this->row_heights ?? [];
        $heights[$row] = $height;
        $this->row_heights = $heights;
    }
}
