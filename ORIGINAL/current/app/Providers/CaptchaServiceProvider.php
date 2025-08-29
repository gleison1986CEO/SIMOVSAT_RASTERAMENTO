<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Tobuli\Services\Captcha\Captchas\DefaultCaptcha;
use Tobuli\Services\Captcha\Captchas\NullCaptcha;
use Tobuli\Services\Captcha\Captchas\ReCaptcha;

class CaptchaServiceProvider extends ServiceProvider
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
        $this->app->singleton('captchaService', function ($app) {
            $captchaProviders = [
                'none' => NullCaptcha::class,
                'default' => DefaultCaptcha::class,
                'recaptcha' => ReCaptcha::class,
            ];

            $selectedProvider = settings('main_settings.captcha_provider');

            $captchaProvider = $captchaProviders[$selectedProvider] ?? NullCaptcha::class;

            return new $captchaProvider;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['captchaService'];
    }
}
