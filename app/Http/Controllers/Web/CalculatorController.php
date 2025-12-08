<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Calculator\CalculatorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalculatorController extends Controller
{
    public function __construct(
        private readonly CalculatorService $calculatorService
    ) {}

    /**
     * Mostrar lista de calculators
     */
    public function index(Request $request): Response
    {
        $calculators = $this->calculatorService->getUserCalculators($request->user()->id);

        return Inertia::render('Calculator/List', [
            'calculators' => $calculators,
        ]);
    }

    /**
     * Mostrar un calculator específico
     */
    public function show(Request $request, string $id): Response
    {
        $calculator = $this->calculatorService->getCalculator($id);

        if (!$calculator) {
            abort(404, 'Calculator no encontrado');
        }

        // Verificar que el usuario tenga acceso
        if ($calculator->user_id !== $request->user()->id) {
            abort(403, 'No tienes acceso a este calculator');
        }

        return Inertia::render('Calculator/Index', [
            'calculator' => [
                'id' => $calculator->id,
                'name' => $calculator->name,
                'data' => $calculator->data ?? [],
                'lastCursorPosition' => $calculator->last_cursor_position ?? ['row' => 0, 'col' => 0],
                'columnWidths' => $calculator->column_widths ?? [],
                'rowHeights' => $calculator->row_heights ?? [],
                'frozenRows' => $calculator->frozen_rows ?? 0,
                'frozenColumns' => $calculator->frozen_columns ?? 0,
                'version' => $calculator->version ?? 0,
            ],
        ]);
    }

    /**
     * Crear un nuevo calculator
     */
    public function create(Request $request)
    {
        $calculator = $this->calculatorService->createCalculator(
            $request->user()->id,
            $request->input('client_id'),
            $request->input('name')
        );

        return redirect()->route('calculator.show', $calculator->id);
    }
}
