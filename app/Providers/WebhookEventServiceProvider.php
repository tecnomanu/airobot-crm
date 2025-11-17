<?php

namespace App\Providers;

use App\Services\Webhook\Strategies\RegisterPhoneEventStrategy;
use App\Services\Webhook\WebhookEventManager;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para el sistema de eventos de webhook
 *
 * Registra el WebhookEventManager como singleton y configura
 * todas las estrategias disponibles.
 */
class WebhookEventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar el WebhookEventManager como singleton
        $this->app->singleton(WebhookEventManager::class, function ($app) {
            $manager = new WebhookEventManager;

            // Registrar todas las estrategias disponibles
            $this->registerStrategies($manager, $app);

            return $manager;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Registra todas las estrategias de eventos disponibles
     *
     * Para agregar una nueva estrategia:
     * 1. Crear clase que implemente WebhookEventStrategyInterface
     * 2. Agregarla aquí con $manager->registerStrategy()
     */
    private function registerStrategies(WebhookEventManager $manager, $app): void
    {
        // Estrategia: webhook_register_phone
        $manager->registerStrategy(
            $app->make(RegisterPhoneEventStrategy::class)
        );

        // Agregar más estrategias aquí según sea necesario
        // Ejemplo:
        // $manager->registerStrategy($app->make(OtraEstrategiaStrategy::class));
    }
}
