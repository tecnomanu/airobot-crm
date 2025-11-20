<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\WebhookSenderInterface;
use App\Contracts\WhatsAppSenderInterface;
use App\Services\External\EvolutionWhatsAppSender;
use App\Services\External\HttpWebhookSender;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para servicios externos (WhatsApp, Webhooks, etc.)
 */
class ExternalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // WhatsApp Sender - Evolution API por defecto
        $this->app->bind(
            WhatsAppSenderInterface::class,
            EvolutionWhatsAppSender::class
        );

        // Webhook Sender - HTTP por defecto
        $this->app->bind(
            WebhookSenderInterface::class,
            HttpWebhookSender::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
