<?php

namespace App\Handlers\Events;

use Illuminate\Auth\Events\Login;
use Tobuli\Entities\User;
use Tobuli\Services\NotificationService;

class AuthLoginEventHandler {

    /**
     * Create the event handler.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Login $event
     * @return void
     */
    public function handle(Login $event)
    {
        session()->put('hash', $event->user->password_hash);

        $notificationService = new NotificationService();
        $notificationService->check($event->user);
    }

}