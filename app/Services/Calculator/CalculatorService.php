<?php

declare(strict_types=1);

namespace App\Services\Calculator;

use App\Models\Calculator;
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
}
