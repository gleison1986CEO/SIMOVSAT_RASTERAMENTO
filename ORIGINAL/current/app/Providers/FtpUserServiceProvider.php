<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tobuli\Services\FtpUserService;

class FtpUserServiceProvider extends ServiceProvider
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
        $this->app->singleton('Tobuli\Services\FtpUserService', function ($app) {
            return new FtpUserService();
        });
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Tobuli\Services\FtpUserService'];
    }
}
