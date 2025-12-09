<?php

declare(strict_types=1);

namespace App\Services\Calculator;

use App\Events\Calculator\CellUpdated;
use App\Events\Calculator\CellRangeUpdated;
use App\Events\Calculator\ColumnResized;
use App\Events\Calculator\RowResized;
use App\Events\Calculator\ColumnInserted;
use App\Events\Calculator\RowInserted;
use App\Events\Calculator\ColumnDeleted;
use App\Events\Calculator\RowDeleted;
use App\Events\Calculator\NameUpdated;
use App\Events\Calculator\CursorMoved;
use App\Models\Tool\Calculator;
use App\Repositories\Interfaces\CalculatorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CalculatorService
{
    public function __construct(
        private readonly CalculatorRepositoryInterface $calculatorRepository
    ) {}

    /**
     * Obtener todos los calculators de un usuario
     */
    public function getUserCalculators(int $userId): Collection
    {
        return $this->calculatorRepository->getAllByUser($userId);
    }

    /**
     * Obtener un calculator por ID
     */
    public function getCalculator(string $id): ?Calculator
    {
        return $this->calculatorRepository->findById($id);
    }

    /**
     * Crear un nuevo calculator
     */
    public function createCalculator(int $userId, ?int $clientId = null, ?string $name = null): Calculator
    {
        return $this->calculatorRepository->create([
            'user_id' => $userId,
            'client_id' => $clientId,
            'name' => $name ?? 'Hoja sin título',
            'data' => [],
            'last_cursor_position' => ['row' => 0, 'col' => 0],
            'column_widths' => [],
            'row_heights' => [],
            'frozen_rows' => 0,
            'frozen_columns' => 0,
        ]);
    }

    /**
     * Actualizar nombre del calculator
     */
    public function updateName(string $id, string $name): bool
    {
        return $this->calculatorRepository->update($id, ['name' => $name]);
    }

    /**
     * Eliminar un calculator
     */
    public function deleteCalculator(string $id): bool
    {
        return $this->calculatorRepository->delete($id);
    }

    /**
     * Guardar estado completo del calculator
     */
    public function saveState(string $id, array $state): bool
    {
        return $this->calculatorRepository->updateState($id, $state);
    }

    /**
     * Actualizar posición del cursor
     */
    public function updateCursorPosition(string $id, int $row, int $col): bool
    {
        return $this->calculatorRepository->updateCursorPosition($id, [
            'row' => $row,
            'col' => $col,
        ]);
    }

    /**
     * Actualizar ancho de columna
     */
    public function updateColumnWidth(string $id, string $column, int $width): bool
    {
        $calculator = $this->calculatorRepository->findById($id);
        if (!$calculator) {
            return false;
        }

        $widths = $calculator->column_widths ?? [];
        $widths[$column] = $width;

        return $this->calculatorRepository->updateColumnWidths($id, $widths);
    }

    /**
     * Actualizar altura de fila
     */
    public function updateRowHeight(string $id, int $row, int $height): bool
    {
        $calculator = $this->calculatorRepository->findById($id);
        if (!$calculator) {
            return false;
        }

        $heights = $calculator->row_heights ?? [];
        $heights[$row] = $height;

        return $this->calculatorRepository->updateRowHeights($id, $heights);
    }

    // =========================================================================
    // MÉTODOS CON EVENT SOURCING Y BROADCAST
    // =========================================================================

    /**
     * Actualizar celda con evento broadcast
     */
    public function updateCellWithEvent(
        string $calculatorId,
        string $cellId,
        mixed $value,
        ?array $format,
        int $expectedVersion,
        int $userId,
        string $userName
    ): ?int {
        $newVersion = $this->calculatorRepository->updateCellWithVersion(
            $calculatorId,
            $cellId,
            $value,
            $format,
            $expectedVersion
        );

        if ($newVersion === null) {
            return null; // Conflicto de versión
        }

        // Emitir evento broadcast solo a otros sockets (evita duplicar en el emisor)
        broadcast(new CellUpdated(
            $calculatorId,
            $cellId,
            $value,
            $format,
            $newVersion,
            $userId,
            $userName
        ))->toOthers();

        return $newVersion;
    }

    /**
     * Actualizar rango de celdas con evento broadcast
     */
    public function updateCellRangeWithEvent(
        string $calculatorId,
        array $cells,
        int $expectedVersion,
        int $userId,
        string $userName
    ): ?int {
        $calculator = $this->calculatorRepository->findById($calculatorId);
        if (!$calculator || $calculator->version !== $expectedVersion) {
            return null;
        }

        // Actualizar múltiples celdas
        $data = $calculator->data ?? [];
        foreach ($cells as $cell) {
            $data[$cell['cellId']] = array_filter([
                'value' => $cell['value'],
                'format' => $cell['format'] ?? null,
            ], fn($v) => $v !== null);
        }

        $calculator->data = $data;
        $calculator->increment('version');
        $calculator->save();

        $newVersion = $calculator->version;

        // Emitir evento broadcast solo a otros sockets
        broadcast(new CellRangeUpdated(
            $calculatorId,
            $cells,
            $newVersion,
            $userId,
            $userName
        ))->toOthers();

        return $newVersion;
    }

    /**
     * Cambiar ancho de columna con evento broadcast
     */
    public function resizeColumnWithEvent(
        string $calculatorId,
        string $column,
        int $width,
        int $expectedVersion,
        int $userId,
        string $userName
    ): ?int {
        $newVersion = $this->calculatorRepository->updateColumnWidthWithVersion(
            $calculatorId,
            $column,
            $width,
            $expectedVersion
        );

        if ($newVersion === null) {
            return null;
        }

        // Emitir evento broadcast solo a otros sockets
        broadcast(new ColumnResized(
            $calculatorId,
            $column,
            $width,
            $newVersion,
            $userId,
            $userName
        ))->toOthers();

        return $newVersion;
    }

    /**
     * Cambiar altura de fila con evento broadcast
     */
    public function resizeRowWithEvent(
        string $calculatorId,
        int $row,
        int $height,
        int $expectedVersion,
        int $userId,
        string $userName
    ): ?int {
        $newVersion = $this->calculatorRepository->updateRowHeightWithVersion(
            $calculatorId,
            $row,
            $height,
            $expectedVersion
        );

        if ($newVersion === null) {
            return null;
        }

        // Emitir evento broadcast solo a otros sockets
        broadcast(new RowResized(
            $calculatorId,
            $row,
            $height,
            $newVersion,
            $userId,
            $userName
        ))->toOthers();

        return $newVersion;
    }

    /**
     * Actualizar nombre con evento broadcast
     */
    public function updateNameWithEvent(
        string $calculatorId,
        string $name,
        int $userId,
        string $userName
    ): bool {
        $calculator = $this->calculatorRepository->findById($calculatorId);
        if (!$calculator) {
            return false;
        }

        $calculator->name = $name;
        $calculator->increment('version');
        $calculator->save();

        // Emitir evento broadcast solo a otros sockets
        broadcast(new NameUpdated(
            $calculatorId,
            $name,
            $calculator->version,
            $userId,
            $userName
        ))->toOthers();

        return true;
    }

    /**
     * Mover cursor con evento broadcast (para presencia)
     */
    public function moveCursorWithEvent(
        string $calculatorId,
        string $cellId,
        int $userId,
        string $userName,
        string $userColor
    ): void {
        // Solo emitir evento, no guardar en BD (a otros sockets)
        broadcast(new CursorMoved(
            $calculatorId,
            $cellId,
            $userId,
            $userName,
            $userColor
        ))->toOthers();
    }
}
