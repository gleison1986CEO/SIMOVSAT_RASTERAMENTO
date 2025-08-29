<?php namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Illuminate\Auth\Events\Login' => [
            'App\Handlers\Events\AuthLoginEventHandler',
            'App\Listeners\SetActingUser',
        ],
        'Illuminate\Auth\Events\Logout' => [
            'App\Handlers\Events\AuthLogoutEventHandler',
        ],
        'App\Events\NewMessage' => [
            'App\Listeners\NewMessageListener'
        ],
        'App\Events\TranslationUpdated' => [
            'App\Listeners\TranslationUpdatedListener',
        ],
        'App\Events\TaskStatusChange' => [
            'App\Listeners\TaskCompletedListener',
        ],
        'App\Events\DeviceSubscriptionRenew' => [
            'App\Listeners\DeviceExpirationSubscriptionRenewListener',
        ],
        'App\Events\DeviceSubscriptionActivate' => [
            'App\Listeners\DeviceExpirationSubscriptionRenewListener',
        ],
        'App\Events\DeviceSubscriptionExpire' => [
            'App\Listeners\DeviceExpirationSubscriptionExpireListener',
        ],
        'App\Events\DevicePositionChanged' => [
            'App\Listeners\GeofenceMoveListener',
        ],
        'App\Events\DeviceEngineChanged' => [
            'App\Listeners\DeviceResetDriverListener',
            'App\Listeners\DeviceResetRfidSensorListener'
        ],
    ];

    /**
    * Register any other events for your application.
    *
    * @param  \Illuminate\Contracts\Events\Dispatcher  $events
    * @return void
    */
    public function boot()
    {
        parent::boot();
    }

}
