<?php

namespace App\Contracts;

use Illuminate\Http\JsonResponse;

/**
 * Interfaz para estrategias de procesamiento de eventos de webhook
 * 
 * Cada estrategia implementa la lógica para un tipo específico de evento
 * siguiendo el patrón Strategy.
 */
interface WebhookEventStrategyInterface
{
    /**
     * Nombre del evento que esta estrategia maneja
     * 
     * @return string
     */
    public function getEventName(): string;

    /**
     * Procesa el evento con los argumentos proporcionados
     * 
     * @param array $args Argumentos del evento
     * @return JsonResponse
     */
    public function handle(array $args): JsonResponse;

    /**
     * Valida que los argumentos sean válidos para este evento
     * 
     * @param array $args
     * @return array Array de errores (vacío si es válido)
     */
    public function validate(array $args): array;
}

