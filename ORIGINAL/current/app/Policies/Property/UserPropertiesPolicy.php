<?php

namespace App\Policies\Property;

use Illuminate\Database\Eloquent\Model;
use Tobuli\Entities\User;

class UserPropertiesPolicy extends PropertyPolicy
{
    protected $editable = [
        'billing_plan_id',
        'devices_limit',
        'subscription_expiration',
        'expiration_date',
        'group_id',
        'manager_id'
    ];

    protected function _edit(User $user, Model $model, $property)
    {
        if ($model->id === $user->id)
            return false;

        return true;
    }

    protected function _view(User $user, Model $model, $property)
    {
        if ($model->id === $user->id)
            return false;

        return true;
    }
}