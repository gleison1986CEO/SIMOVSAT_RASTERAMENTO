<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tobuli\Services\DeviceConfigUpdateService;

class DeviceConfigUpdateServiceProvider extends ServiceProvider
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
        $this->app->singleton('Tobuli\Services\DeviceConfigUpdateService', function ($app) {
            return new DeviceConfigUpdateService();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Tobuli\Services\DeviceConfigUpdateService'];
    }
}
