<?php

namespace App\Policies;

use Tobuli\Entities\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeviceCameraPolicy extends Policy
{
    protected $permisionKey = 'device_camera';

    protected function ownership(User $user, Model $entity)
    {
        $deviceEntity = $entity->device()->first();

        if (is_null($deviceEntity))
            return false;

        if (method_exists($deviceEntity, 'users') && $deviceEntity->users() instanceof BelongsToMany)
            return $this->ownershipMany($user, $deviceEntity);

        if (method_exists($deviceEntity, 'users') && $deviceEntity->users() instanceof HasMany)
            return $this->ownershipMany($user, $deviceEntity);

        if (method_exists($deviceEntity, 'user') && $deviceEntity->user() instanceof BelongsTo)
            return $this->ownershipOne($user, $deviceEntity);

        if (method_exists($deviceEntity, 'user') && $deviceEntity->user() instanceof HasOne)
            return $this->ownershipOne($user, $deviceEntity);

        throw new \Exception("Class '".get_class($entity)."' dont have User relations");
    }
}
