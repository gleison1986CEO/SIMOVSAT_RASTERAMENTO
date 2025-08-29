<?php

namespace Tobuli\Helpers\Templates\Builders;

use Tobuli\Entities\User;
use Tobuli\Helpers\Templates\Replacers\UserReplacer;

class ExpiredUserTemplate extends TemplateBuilder
{
    /**
     * @param User $user
     * @return array
     */
    protected function variables($user)
    {
        $userReplacer = (new UserReplacer())->setUser($this->user);

        return array_merge([
            '[days]'   => settings('main_settings.expire_notification.days_after'),
        ], $userReplacer->replacers($user));
    }

    protected function placeholders()
    {
        return array_merge([
            '[days]'   => 'Days after expiration',
        ], (new UserReplacer())->placeholders());
    }
}