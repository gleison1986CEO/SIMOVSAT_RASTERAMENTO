<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use Tobuli\Entities\User;

class BaseTransformer extends TransformerAbstract {

    /**
     * @var User
     */
    protected $user;

    public function __construct()
    {
        $this->user = getActingUser();
    }

    protected function canView($entity, $property, $default = null)
    {
        if (is_null($this->user))
            return $default;

        if ( ! $this->user->can('view', $entity, $property))
            return $default;

        return $entity->{$property};
    }
}