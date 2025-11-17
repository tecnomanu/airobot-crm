<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SourceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Source\SourceResource;
use App\Http\Traits\ApiResponse;
use App\Services\Source\SourceService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador API para Sources (Fuentes)
 * Endpoints para consumo desde frontend y aplicaciones externas
 */
class SourceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SourceService $sourceService
    ) {}

    /**
     * Obtener fuentes activas por tipo (para selects)
     */
    public function getActiveByType(string $type): JsonResponse
    {
        try {
            $sourceType = SourceType::from($type);
            $sources = $this->sourceService->getByType($sourceType, activeOnly: true);

            return $this->successResponse(
                SourceResource::collection($sources),
                'Sources retrieved successfully'
            );

        } catch (\ValueError $e) {
            return $this->validationErrorResponse('Invalid source type');
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Error retrieving sources',
                config('app.debug') ? $e->getMessage() : ''
            );
        }
    }
}
