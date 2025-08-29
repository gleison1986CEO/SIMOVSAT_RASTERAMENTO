<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Tobuli\Helpers\Formatter\Formatter;

class FormatterServiceProvider extends ServiceProvider {

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Formatter', function ($app) {
            return new Formatter();
        });
    }

}