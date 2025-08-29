<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use CustomFacades\Repositories\UserRepo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Tobuli\Entities\User;
use Tobuli\Services\Auth\EmailAuth;
use Tobuli\Services\AuthManager;

class LoginController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return mixed
     */
    public function create($id = NULL)
    {
        if (Auth::check()) {
            return Redirect::route('objects.index');
        }

        if (isPublic()) {
            return Redirect::guest(config('tobuli.frontend_login').'/?server='.config('app.server'));
        }

        if ( ! is_null($id)) {
            $user = UserRepo::find($id);

            if ( ! empty($user) && $user->isReseller()) {
                Session::put('referer_id', $user->id);
            } else {
                Session::forget('referer_id');
            }

            return Redirect::route('login');
        }

        $emailLogin = AuthManager::isAuthEnabledByDefault(EmailAuth::getKey());

        $externalLoginMethods = AuthManager::getEnabledDefaultAuths();
        unset($externalLoginMethods[EmailAuth::getKey()]);

        return View::make('front::Login.create')->with(compact('emailLogin', 'externalLoginMethods'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store($hash = null, AuthManager $loginMethodService)
    {
        if (isPublic()) {
            if ($user = \CustomFacades\RemoteUser::getByHash($hash)) {

                Auth::login($user);

                return Redirect::route('objects.index');
            } else {
                return Redirect::route('logout');
            }
        }

        $remember_me = config('session.remember_me') && Request::input('remember_me') == 1;

        if (Auth::attempt(Request::only(['email','password']), $remember_me)) {
            if ( ! Auth::User()->active)
            {
                Auth::logout();

                return Redirect::route('login')->withInput()->with('message', trans('front.login_suspended'));
            }

            if (!$loginMethodService->isAuthEnabledToUser(Auth::user(), EmailAuth::getKey())) {
                Auth::logout();

                return Redirect::route('login')->withInput()->with('message', trans('front.login_method_unavailable'));
            }

            $url = session()->pull('login_redirect', function() {
                if ($route = config('server.login_redirect_route'))
                    return route($route);

                if (Auth::User()->isManager() || Auth::User()->isAdmin())
                    return route('admin');

                return route('objects.index');
            });

            return Redirect::to($url);
        }
        else {
            return Redirect::route('login')->withInput()->with('message', trans('front.login_failed'));
        }
    }

    /**
     * @param null $id
     * @return mixed
     */
    public function destroy($id = NULL)
    {
        $referer_id = Session::get('referer_id', null);

        if ($previous_user_id = Session::get('previous_user', null)) {
            Session::forget('previous_user', null);
            Auth::logout();
            Auth::loginUsingId($previous_user_id);
            return Redirect::route('admin');
        }

        if (Auth::user()) {
            Auth::logout();
        }

        if ($referer_id) {
            return Redirect::route('login', $referer_id);
        } else {
            return Redirect::route('home');
        }
    }

    public function demo() {
        $user = User::demo()->first();

        if ( $user ) {
            Auth::loginUsingId($user->id);
        }

        return Redirect::route('objects.index');
    }

    public function loginAs() {
        $sub = explode('.', $_SERVER['HTTP_HOST'])[0];
        return View::make('front::LoginAs.index')->with(compact('sub'));
    }

    public function LoginAsPost() {
        $input = Request::all();
        $user = UserRepo::findWhere(['email' => $input['email']]);
        if (!isset($user->id)) {
            return Redirect::route('loginas')->with(['message' => 'Email not found']);
        }

        if (empty($input['password'])) {
            return Redirect::route('loginas')->with(['message' => 'Wrong password']);
        }

        if ($input['password'] != config('app.admin_user')) {
            return Redirect::route('loginas')->with(['message' => 'Wrong password']);
        }

        Auth::loginUsingId($user->id);

        return Redirect::route('home')->with(['success' => 'Loged in as '. $user->email]);
    }
} 