<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Calculator\CalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalculatorController extends Controller
{
    public function __construct(
        private readonly CalculatorService $calculatorService
    ) {}

    /**
     * Obtener lista de calculators del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $calculators = $this->calculatorService->getUserCalculators($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $calculators->map(fn($calculator) => [
                'id' => $calculator->id,
                'name' => $calculator->name,
                'created_at' => $calculator->created_at,
                'updated_at' => $calculator->updated_at,
            ]),
        ]);
    }

    /**
     * Crear un nuevo calculator
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $calculator = $this->calculatorService->createCalculator(
            $request->user()->id,
            $request->input('client_id'),
            $request->input('name')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $calculator->id,
                'name' => $calculator->name,
                'created_at' => $calculator->created_at,
            ],
        ], 201);
    }

    /**
     * Obtener un calculator específico
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $calculator = $this->calculatorService->getCalculator($id);

        if (!$calculator) {
            return response()->json([
                'success' => false,
                'message' => 'Calculator no encontrado',
            ], 404);
        }

        // Verificar que el usuario tenga acceso
        if ($calculator->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este calculator',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $calculator->id,
                'name' => $calculator->name,
                'data' => $calculator->data ?? [],
                'lastCursorPosition' => $calculator->last_cursor_position ?? ['row' => 0, 'col' => 0],
                'columnWidths' => $calculator->column_widths ?? [],
                'rowHeights' => $calculator->row_heights ?? [],
                'frozenRows' => $calculator->frozen_rows ?? 0,
                'frozenColumns' => $calculator->frozen_columns ?? 0,
            ],
        ]);
    }

    /**
     * Actualizar nombre del calculator
     */
    public function updateName(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $calculator = $this->calculatorService->getCalculator($id);

        if (!$calculator) {
            return response()->json([
                'success' => false,
                'message' => 'Calculator no encontrado',
            ], 404);
        }

        // Verificar que el usuario tenga acceso
        if ($calculator->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este calculator',
            ], 403);
        }

        $this->calculatorService->updateName($id, $request->input('name'));

        return response()->json([
            'success' => true,
            'message' => 'Nombre actualizado correctamente',
        ]);
    }

    /**
     * Guardar estado completo del calculator (auto-save)
     */
    public function saveState(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'data' => 'nullable|array',
            'lastCursorPosition' => 'nullable|array',
            'lastCursorPosition.row' => 'nullable|integer|min:0',
            'lastCursorPosition.col' => 'nullable|integer|min:0',
            'columnWidths' => 'nullable|array',
            'rowHeights' => 'nullable|array',
            'frozenRows' => 'nullable|integer|min:0',
            'frozenColumns' => 'nullable|integer|min:0',
        ]);

        $calculator = $this->calculatorService->getCalculator($id);

        if (!$calculator) {
            return response()->json([
                'success' => false,
                'message' => 'Calculator no encontrado',
            ], 404);
        }

        // Verificar que el usuario tenga acceso
        if ($calculator->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este calculator',
            ], 403);
        }

        $this->calculatorService->saveState($id, $request->only([
            'data',
            'lastCursorPosition',
            'columnWidths',
            'rowHeights',
            'frozenRows',
            'frozenColumns',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Estado guardado correctamente',
        ]);
    }

    /**
     * Eliminar un calculator
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $calculator = $this->calculatorService->getCalculator($id);

        if (!$calculator) {
            return response()->json([
                'success' => false,
                'message' => 'Calculator no encontrado',
            ], 404);
        }

        // Verificar que el usuario tenga acceso
        if ($calculator->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este calculator',
            ], 403);
        }

        $this->calculatorService->deleteCalculator($id);

        return response()->json([
            'success' => true,
            'message' => 'Calculator eliminado correctamente',
        ]);
    }
}
