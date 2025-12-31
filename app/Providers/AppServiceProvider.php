<?php

namespace App\Providers;

use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Observers\LeadObserver;
use App\Policies\CampaignPolicy;
use App\Policies\ClientPolicy;
use App\Policies\LeadPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure HTTPS and URL scheme
        $this->configureHttpsAndUrls();

        // Register model policies for authorization
        $this->registerPolicies();

        Vite::prefetch(concurrency: 3);
        Model::shouldBeStrict(! $this->app->environment('production'));

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\LeadUpdated::class,
            \App\Listeners\ExportLeadToGoogleSheet::class
        );

        // Register lead domain event subscriber for broadcasting
        \Illuminate\Support\Facades\Event::subscribe(
            \App\Listeners\Lead\BroadcastLeadChanges::class
        );

        // Register Lead observer for auto-assignment on Sales Ready
        Lead::observe(LeadObserver::class);
    }

    /**
     * Configure HTTPS and URL generation for the application.
     */
    private function configureHttpsAndUrls(): void
    {
        // Never force HTTPS in local/development environments
        if ($this->app->environment(['local', 'development', 'testing'])) {
            return;
        }

        // Force HTTPS in production/staging or when behind HTTPS proxy
        if (
            $this->app->environment(['production', 'staging']) ||
            $this->isRunningBehindHttpsProxy()
        ) {
            // Set HTTPS flag on the request
            if ($this->app->bound('request')) {
                $this->app['request']->server->set('HTTPS', 'on');
            }

            // Force URL scheme to HTTPS
            \Illuminate\Support\Facades\URL::forceScheme('https');

            // Also force root URL if APP_URL doesn't have https
            $appUrl = config('app.url');
            if ($appUrl && !str_starts_with($appUrl, 'https://')) {
                $httpsUrl = str_replace('http://', 'https://', $appUrl);
                \Illuminate\Support\Facades\URL::forceRootUrl($httpsUrl);
                config(['app.url' => $httpsUrl]);
            }
        }
    }

    /**
     * Determine if the application is running behind an HTTPS proxy.
     */
    private function isRunningBehindHttpsProxy(): bool
    {
        $request = request();

        if (!$request) {
            return false;
        }

        // Check HTTPS proxy headers
        return $request->header('X-Forwarded-Proto') === 'https' ||
            $request->header('X-Forwarded-SSL') === 'on' ||
            $request->header('CloudFront-Forwarded-Proto') === 'https' ||
            $request->server('HTTPS') === 'on';
    }

    /**
     * Register model policies for authorization.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
    }
}
