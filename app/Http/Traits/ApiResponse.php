<?php

declare(strict_types=1);

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Trait para estandarizar respuestas API
 *
 * Formato de respuestas:
 * - Success: { success: true, message: "", data: {}, metadata: {} }
 * - Error: { success: false, message: "", error: "" }
 */
trait ApiResponse
{
    /**
     * Respuesta exitosa con datos
     */
    protected function successResponse(
        mixed $data = null,
        string $message = '',
        array $metadata = [],
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        // Si es un Resource o ResourceCollection, transformar
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            $response['data'] = $data->resolve();
        } elseif ($data !== null) {
            $response['data'] = $data;
        }

        // Agregar metadata si existe
        if (! empty($metadata)) {
            $response['metadata'] = $metadata;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Respuesta de creación exitosa
     */
    protected function createdResponse(
        mixed $data,
        string $message = 'Resource created successfully',
        array $metadata = []
    ): JsonResponse {
        return $this->successResponse($data, $message, $metadata, 201);
    }

    /**
     * Respuesta de actualización exitosa
     */
    protected function updatedResponse(
        mixed $data,
        string $message = 'Resource updated successfully',
        array $metadata = []
    ): JsonResponse {
        return $this->successResponse($data, $message, $metadata, 200);
    }

    /**
     * Respuesta de eliminación exitosa
     */
    protected function deletedResponse(
        string $message = 'Resource deleted successfully'
    ): JsonResponse {
        return $this->successResponse(null, $message, [], 200);
    }

    /**
     * Respuesta de error general
     */
    protected function errorResponse(
        string $message,
        string $error = '',
        int $statusCode = 400,
        array $metadata = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($error)) {
            $response['error'] = $error;
        }

        if (! empty($metadata)) {
            $response['metadata'] = $metadata;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Respuesta de validación fallida
     */
    protected function validationErrorResponse(
        string $message = 'Validation error',
        array $errors = []
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            '',
            422,
            ['errors' => $errors]
        );
    }

    /**
     * Respuesta de recurso no encontrado
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse($message, '', 404);
    }

    /**
     * Respuesta de no autorizado
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->errorResponse($message, '', 401);
    }

    /**
     * Respuesta de prohibido
     */
    protected function forbiddenResponse(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->errorResponse($message, '', 403);
    }

    /**
     * Respuesta de error interno del servidor
     */
    protected function serverErrorResponse(
        string $message = 'Internal server error',
        string $error = ''
    ): JsonResponse {
        return $this->errorResponse($message, $error, 500);
    }
}
