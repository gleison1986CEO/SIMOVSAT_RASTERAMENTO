<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\AuthManager;

class AuthConfigController extends BaseController
{
    private $authManager;

    public function __construct(AuthManager $authManager)
    {
        parent::__construct();

        $this->authManager = $authManager;
    }

    public function index()
    {
        $generalSettings = settings('user_login_methods.general');
        $auths = $this->authManager->getAuths();

        return view('admin::AuthConfig.index')->with(compact('generalSettings', 'auths'));
    }

    public function store()
    {
        $input = Input::all();

        try {
            $this->authManager->storeGeneralSettings($input);
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->getErrors());
        }

        return Redirect::back()->withSuccess(trans('front.successfully_saved'));
    }

    public function storeAuth(string $authKey)
    {
        $input = Input::all();

        try {
            $this->authManager->storeConfig($authKey, $input);
        } catch (ValidationException $e) {
            return Redirect::back()->with(['errors-' . $authKey => $e->getErrors()]);
        }

        return Redirect::back()->withSuccess(trans('front.successfully_saved') . ' ' . trans("validation.login_methods.$authKey"));
    }

    public function check(string $authKey)
    {
        $input = Input::all();

        $errors = $this->authManager->checkConfigErrors($authKey, $input);

        return response()->json(['errors' => $errors]);
    }
}
