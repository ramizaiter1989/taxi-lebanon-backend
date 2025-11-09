<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\Ride;
use App\Observers\RideObserver;
use App\Services\GeocodingService;
use App\Services\RouteService;
use Illuminate\Support\Facades\Notification;
use App\Channels\ExpoChannel;
use App\Models\Driver;
use App\Observers\DriverObserver;

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
        Driver::observe(DriverObserver::class);

        Notification::extend('expo', function ($app) {
        return new ExpoChannel($app->make(\App\Services\ExpoPushNotificationService::class));
    });
    }
}
