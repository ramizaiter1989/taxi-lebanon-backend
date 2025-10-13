<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\Ride;
use App\Observers\RideObserver;
use App\Services\GeocodingService;
use App\Services\RouteService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
        public function register()
    {
        $this->app->singleton(GeocodingService::class, function ($app) {
            return new GeocodingService();
        });
        $this->app->singleton(RouteService::class, function ($app) {
            return new RouteService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS URLs if not in local environment
        if ($this->app->environment() !== 'local') {
            URL::forceScheme('https');
        }
        Ride::observe(RideObserver::class);
    }
}
