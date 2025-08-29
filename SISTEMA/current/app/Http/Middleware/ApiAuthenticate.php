<?php namespace App\Http\Middleware;

use Closure;
use Language;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Tobuli\Entities\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;

class ApiAuthenticate {

	/**
	 * Create a new filter instance.
	 *
	 * @param  Guard  $auth
	 * @return void
	 */
	public function __construct()
	{
		Config::set('tobuli.api', 1);
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
		$user = null;

		if ($hash = $this->getApiHash($request)) {
			$user = User::where('api_hash', $hash)->first();

			if (isPublic()) {
                if (empty($user) || strtotime($user->api_hash_expire) < time()) {
                    $user = \CustomFacades\RemoteUser::getByApiHash($hash);
                }
            }
		}

		if (empty($user))
			return response()->json(['status' => 0, 'message' => trans('front.login_failed')], 401);

        if ( ! $user->active)
            return response()->json(['status' => 0, 'message' => trans('front.login_suspended')], 401);

        Auth::onceUsingId($user->id);

        setActingUser(Auth::User());

        return $next($request);
	}

    public function terminate($request, $response)
    {
        if ($user = Auth::User()) {
            $user->loged_at = date('Y-m-d H:i:s');
            $user->save();
        }
    }

    private function getApiHash($request)
    {
        $hash = null;

        if ($hash = $request->get('user_api_hash'))
            return $hash;

        if ($hash = $request->header('user-api-hash'))
            return $hash;

        return null;
    }

}
