<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Calculator;
use App\Repositories\Interfaces\CalculatorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCalculatorRepository implements CalculatorRepositoryInterface
{
    public function __construct(
        private readonly Calculator $model
    ) {}

    public function getAllByUser(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function findById(string $id): ?Calculator
    {
        return $this->model->find($id);
    }

    public function create(array $data): Calculator
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): bool
    {
        return (bool) $this->model
            ->where('id', $id)
            ->update($data);
    }

    public function delete(string $id): bool
    {
        return (bool) $this->model
            ->where('id', $id)
            ->delete();
    }

    public function updateCellData(string $id, array $cellData): bool
    {
        $calculator = $this->findById($id);
        if (!$calculator) {
            return false;
        }

        $calculator->data = $cellData;
        return $calculator->save();
    }

    public function updateCursorPosition(string $id, array $position): bool
    {
        return (bool) $this->model
            ->where('id', $id)
            ->update(['last_cursor_position' => $position]);
    }

    public function updateColumnWidths(string $id, array $widths): bool
    {
        return (bool) $this->model
            ->where('id', $id)
            ->update(['column_widths' => $widths]);
    }

    public function updateRowHeights(string $id, array $heights): bool
    {
        return (bool) $this->model
            ->where('id', $id)
            ->update(['row_heights' => $heights]);
    }

    public function updateState(string $id, array $state): bool
    {
        $updateData = [];

        if (isset($state['data'])) {
            $updateData['data'] = $state['data'];
        }
        if (isset($state['lastCursorPosition'])) {
            $updateData['last_cursor_position'] = $state['lastCursorPosition'];
        }
        if (isset($state['columnWidths'])) {
            $updateData['column_widths'] = $state['columnWidths'];
        }
        if (isset($state['rowHeights'])) {
            $updateData['row_heights'] = $state['rowHeights'];
        }
        if (isset($state['frozenRows'])) {
            $updateData['frozen_rows'] = $state['frozenRows'];
        }
        if (isset($state['frozenColumns'])) {
            $updateData['frozen_columns'] = $state['frozenColumns'];
        }

        if (empty($updateData)) {
            return true;
        }

        return $this->update($id, $updateData);
    }

    public function incrementVersion(string $id): int
    {
        $calculator = $this->findById($id);
        if (!$calculator) {
            return 0;
        }

        $calculator->increment('version');
        $calculator->refresh();

        return $calculator->version;
    }

    public function getVersion(string $id): ?int
    {
        $calculator = $this->findById($id);
        return $calculator?->version;
    }

    public function updateCellWithVersion(string $id, string $cellId, mixed $value, ?array $format, int $expectedVersion): ?int
    {
        $calculator = $this->findById($id);
        if (!$calculator) {
            return null;
        }

        // Verificar versión
        if ($calculator->version !== $expectedVersion) {
            return null; // Conflicto de versión
        }

        // Actualizar celda
        $data = $calculator->data ?? [];
        $data[$cellId] = array_filter([
            'value' => $value,
            'format' => $format,
        ], fn($v) => $v !== null);

        $calculator->data = $data;
        $calculator->increment('version');
        $calculator->save();

        return $calculator->version;
    }

    public function updateColumnWidthWithVersion(string $id, string $column, int $width, int $expectedVersion): ?int
    {
        $calculator = $this->findById($id);
        if (!$calculator) {
            return null;
        }

        // Verificar versión
        if ($calculator->version !== $expectedVersion) {
            return null; // Conflicto de versión
        }

        // Actualizar ancho de columna
        $widths = $calculator->column_widths ?? [];
        $widths[$column] = $width;

        $calculator->column_widths = $widths;
        $calculator->increment('version');
        $calculator->save();

        return $calculator->version;
    }

    public function updateRowHeightWithVersion(string $id, int $row, int $height, int $expectedVersion): ?int
    {
        $calculator = $this->findById($id);
        if (!$calculator) {
            return null;
        }

        // Verificar versión
        if ($calculator->version !== $expectedVersion) {
            return null; // Conflicto de versión
        }

        // Actualizar altura de fila
        $heights = $calculator->row_heights ?? [];
        $heights[$row] = $height;

        $calculator->row_heights = $heights;
        $calculator->increment('version');
        $calculator->save();

        return $calculator->version;
    }
}
