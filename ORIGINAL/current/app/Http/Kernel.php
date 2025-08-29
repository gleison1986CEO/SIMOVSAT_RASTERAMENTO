<?php namespace App\Http;

use App\Http\Middleware\ConfirmedAction;
use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {

	/**
	 * The application's global HTTP middleware stack.
	 *
	 * @var array
	 */
	protected $middleware = [
		'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
		'Illuminate\Cookie\Middleware\EncryptCookies',
		'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
		'Illuminate\Session\Middleware\StartSession',
		'Illuminate\View\Middleware\ShareErrorsFromSession',
		'App\Http\Middleware\SetLang',
        'App\Http\Middleware\Referer',
        'App\Http\Middleware\ServerActive',
        'App\Http\Middleware\EnsureEmailIsVerified',
		'Fideloper\Proxy\TrustProxies',
		//'App\Http\Middleware\VerifyCsrfToken'
	];

	/**
	 * The application's route middleware.
	 *
	 * @var array
	 */
	protected $routeMiddleware = [
	    'confirmed_action' => ConfirmedAction::class,
	    'auth' => 'App\Http\Middleware\Authenticate',
		'auth.basic' => 'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth',
        'auth.api' => 'App\Http\Middleware\ApiAuthenticate',
        'auth.tracker' => 'App\Http\Middleware\TrackerAuth',
        'auth.admin' => 'App\Http\Middleware\AdminAuthenticate',
        'auth.manager' => 'App\Http\Middleware\ManagerAuthenticate',
		'guest' => 'App\Http\Middleware\RedirectIfAuthenticated',
		'active_subscription' => 'App\Http\Middleware\ActiveSubscription',
        'server_active' => 'App\Http\Middleware\ServerActive',
        'api_active' => 'App\Http\Middleware\ApiActive',
        'bindings' => 'Illuminate\Routing\Middleware\SubstituteBindings',
        'throttle' => 'Illuminate\Routing\Middleware\ThrottleRequests',
        'captcha' => 'App\Http\Middleware\Captcha',
        'verified' => EnsureEmailIsVerified::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            // \App\Http\Middleware\EncryptCookies::class,
            // \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            // \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // 'throttle:60,1',
            // 'bindings',
        ],
    ];
}
