<?php

namespace App\Providers;

use App\Repositories\Eloquent\CallHistoryRepository;
use App\Repositories\Eloquent\CampaignRepository;
use App\Repositories\Eloquent\CampaignWhatsappTemplateRepository;
use App\Repositories\Eloquent\ClientRepository;
use App\Repositories\Eloquent\EloquentCalculatorRepository;
use App\Repositories\Eloquent\LeadInteractionRepository;
use App\Repositories\Eloquent\LeadRepository;
use App\Repositories\Eloquent\SourceRepository;
use App\Repositories\Interfaces\CallHistoryRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\CampaignWhatsappTemplateRepositoryInterface;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Repositories\Interfaces\CalculatorRepositoryInterface;
use App\Repositories\Interfaces\LeadInteractionRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Repositories\Interfaces\SourceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registro de bindings de interfaces a implementaciones
     * Esto permite inyección de dependencias siguiendo el principio de Inversión de Dependencias (SOLID)
     */
    public function register(): void
    {
        // Lead Repository
        $this->app->bind(
            LeadRepositoryInterface::class,
            LeadRepository::class
        );

        // Campaign Repository
        $this->app->bind(
            CampaignRepositoryInterface::class,
            CampaignRepository::class
        );

        // Client Repository
        $this->app->bind(
            ClientRepositoryInterface::class,
            ClientRepository::class
        );

        // CallHistory Repository
        $this->app->bind(
            CallHistoryRepositoryInterface::class,
            CallHistoryRepository::class
        );

        // LeadInteraction Repository
        $this->app->bind(
            LeadInteractionRepositoryInterface::class,
            LeadInteractionRepository::class
        );

        // CampaignWhatsappTemplate Repository
        $this->app->bind(
            CampaignWhatsappTemplateRepositoryInterface::class,
            CampaignWhatsappTemplateRepository::class
        );

        // Source Repository
        $this->app->bind(
            SourceRepositoryInterface::class,
            SourceRepository::class
        );

        // Calculator Repository
        $this->app->bind(
            CalculatorRepositoryInterface::class,
            EloquentCalculatorRepository::class
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
