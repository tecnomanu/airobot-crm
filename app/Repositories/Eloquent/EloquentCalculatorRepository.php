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
}
