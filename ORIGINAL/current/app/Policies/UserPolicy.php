<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\User;

class UserPolicy extends Policy
{
    protected $permisionKey = null;

    protected function ownership(User $user, Model $entity)
    {
        if ($user->isManager() && $user->id == $entity->manager_id)
            return true;

        if ($user->id == $entity->id)
            return true;

        return false;
    }

    public function destroy(User $user, Model $entity = null)
    {
        if ($user->id == $entity->id)
            return false;

        if ($user->isAdmin())
            return true;

        return $this->clean($user, $entity) && $this->ownership($user, $entity);
    }
}
