<?php

namespace App\Services\Webhook;

use App\Contracts\WebhookEventStrategyInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Manager para despachar eventos de webhook a sus estrategias correspondientes
 *
 * Implementa el patrón Strategy para procesar diferentes tipos de eventos
 * de forma desacoplada y extensible.
 */
class WebhookEventManager
{
    /**
     * @var array<string, WebhookEventStrategyInterface>
     */
    private array $strategies = [];

    /**
     * Registrar una estrategia para un evento específico
     */
    public function registerStrategy(WebhookEventStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getEventName()] = $strategy;

        Log::debug('Estrategia de webhook registrada', [
            'event' => $strategy->getEventName(),
            'strategy' => get_class($strategy),
        ]);
    }

    /**
     * Despacha un evento a su estrategia correspondiente
     */
    public function dispatch(string $eventName, array $args): JsonResponse
    {
        // Verificar si existe una estrategia para este evento
        if (! isset($this->strategies[$eventName])) {
            Log::warning('Evento de webhook desconocido', [
                'event' => $eventName,
                'available_events' => array_keys($this->strategies),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unknown event',
                'error' => "No handler found for event: {$eventName}",
                'available_events' => array_keys($this->strategies),
            ], 400);
        }

        $strategy = $this->strategies[$eventName];

        // Validar argumentos
        $validationErrors = $strategy->validate($args);
        if (! empty($validationErrors)) {
            Log::warning('Validación fallida para evento de webhook', [
                'event' => $eventName,
                'errors' => $validationErrors,
            ]);

            return response()->json([
                'success' => false,
                'event' => $eventName,
                'message' => 'Validation failed',
                'errors' => $validationErrors,
            ], 422);
        }

        Log::info('Despachando evento de webhook', [
            'event' => $eventName,
            'strategy' => get_class($strategy),
        ]);

        // Ejecutar estrategia
        return $strategy->handle($args);
    }

    /**
     * Obtiene la lista de eventos disponibles
     */
    public function getAvailableEvents(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Verifica si un evento está registrado
     */
    public function hasEvent(string $eventName): bool
    {
        return isset($this->strategies[$eventName]);
    }
}
