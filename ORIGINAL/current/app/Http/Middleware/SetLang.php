<?php

namespace App\Http\Middleware;

use Closure;
use CustomFacades\Appearance;
use Language;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Session;

class SetLang
{

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
    public function handle($request, Closure $next)
    {
        if ($request->get('lang')) {
            Session::put('language', $request->get('lang'));
        }

        $userLang = $this->auth->check()
            ? $this->auth->user()->lang
            : Appearance::resolveUser()->getSetting('default_language');

        // Get the user specific language
        $lang = Session::has('language')
            ? Session::get('language')
            : $userLang;

        if (! empty($lang)) {
            // Set the language
            Language::set($lang);
        }

        return $next($request);
    }
}
