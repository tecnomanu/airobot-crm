<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Calculator;
use Illuminate\Database\Eloquent\Collection;

interface CalculatorRepositoryInterface
{
    /**
     * Obtener todos los calculators de un usuario
     */
    public function getAllByUser(int $userId): Collection;

    /**
     * Obtener un calculator por ID
     */
    public function findById(string $id): ?Calculator;

    /**
     * Crear un nuevo calculator
     */
    public function create(array $data): Calculator;

    /**
     * Actualizar un calculator
     */
    public function update(string $id, array $data): bool;

    /**
     * Eliminar un calculator
     */
    public function delete(string $id): bool;

    /**
     * Actualizar datos de celdas
     */
    public function updateCellData(string $id, array $cellData): bool;

    /**
     * Actualizar posición del cursor
     */
    public function updateCursorPosition(string $id, array $position): bool;

    /**
     * Actualizar anchos de columnas
     */
    public function updateColumnWidths(string $id, array $widths): bool;

    /**
     * Actualizar alturas de filas
     */
    public function updateRowHeights(string $id, array $heights): bool;

    /**
     * Actualizar todo el estado del calculator
     */
    public function updateState(string $id, array $state): bool;

    /**
     * Incrementar versión y retornar la nueva
     */
    public function incrementVersion(string $id): int;

    /**
     * Obtener versión actual
     */
    public function getVersion(string $id): ?int;

    /**
     * Actualizar celda con versionado
     */
    public function updateCellWithVersion(string $id, string $cellId, mixed $value, ?array $format, int $expectedVersion): ?int;

    /**
     * Actualizar ancho de columna con versionado
     */
    public function updateColumnWidthWithVersion(string $id, string $column, int $width, int $expectedVersion): ?int;

    /**
     * Actualizar altura de fila con versionado
     */
    public function updateRowHeightWithVersion(string $id, int $row, int $height, int $expectedVersion): ?int;
}
