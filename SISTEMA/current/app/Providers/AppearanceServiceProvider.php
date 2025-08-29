<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tobuli\Services\AppearanceService;

class AppearanceServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AppearanceService::class, function ($app) {
            return new AppearanceService();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [AppearanceService::class];
    }
}
