<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ActiveSubscription {

	/**
	 * Create a new filter instance.
	 *
	 * @param  Guard  $auth
	 * @return void
	 */
	public function __construct()
	{
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		if (Auth::User()->isExpired()) {

            if ( config('tobuli.api') )
                return response()->json(['status' => 0, 'message' => trans('front.subscription_expired')], 401);

            if (isPublic()) {
                $email = Auth::User()->email;
                Auth::logout();
                return redirect(config('tobuli.frontend_subscriptions').'?subscription_expired&email='.base64_encode($email).'&server='.config('app.server'));
            }
			if (!is_null(Auth::User()->billing_plan_id)) {
				return redirect(route('payments.subscriptions'))->with(['message' => trans('front.subscription_expired')]);
			}

			Auth::logout();

			return redirect(route('login'))->with(['message' => trans('front.subscription_expired')]);
		}

		return $next($request);
	}

}
