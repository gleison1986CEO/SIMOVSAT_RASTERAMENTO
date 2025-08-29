<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Authenticate {

	/**
	 * The Guard implementation.
	 *
	 * @var Guard
	 */
	protected $auth;

	/**
	 * Create a new filter instance.
	 *
	 * @param  Guard  $auth
	 * @return void
	 */
	public function __construct(Guard $auth)
	{
		$this->auth = $auth;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if ($this->auth->guest())
		{
            return $this->redirect($request);
		}

		if ( ! Auth::User()->active)
		{
            return $this->redirect($request);
		}

		if ($request->session()->has('hash')) {
            if ($request->session()->get('hash') !== Auth::User()->password_hash)
            {
                Auth::logout();

                return $this->redirect($request);
            }
        } else {
            $request->session()->put('hash', Auth::User()->password_hash);
        }

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

	private function redirect(Request $request)
    {
        if ( ! $this->auth->guest())
            $this->auth->logout();

        if ($request->ajax())
            return response('Unauthorized.', 401);

        if (isPublic()) {
            return redirect()->guest(config('tobuli.frontend_login').'/?server='.config('app.server'));
        }

        $request->session()->forget('login_redirect');
        $request->session()->put('login_redirect', $request->getRequestUri());

        return redirect()->guest(route('home'));
    }
}
