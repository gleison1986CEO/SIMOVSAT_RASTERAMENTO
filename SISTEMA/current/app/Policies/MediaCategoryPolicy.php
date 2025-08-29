<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\MediaCategory;
use Tobuli\Entities\User;

class MediaCategoryPolicy extends Policy
{
    protected $permisionKey = 'media_categories';

    /**
     * @param User $user
     * @param MediaCategory|null $entity
     * @return bool
     */
    protected function ownership(User $user, Model $entity = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $entity->user_id && $entity->user_id === $user->id;
    }
}
