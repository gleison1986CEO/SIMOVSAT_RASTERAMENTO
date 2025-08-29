<?php

namespace Tobuli\Repositories\Subscription;

use Tobuli\Entities\Subscription as Entity;
use Tobuli\Repositories\EloquentRepository;

class EloquentSubscriptionRepository extends EloquentRepository implements SubscriptionRepositoryInterface
{
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;

        $this->searchable = [
            'user_id',
            'gateway',
            'gateway_id',
            'expiration_date',
        ];
    }

    public function expired()
    {
        $this->entity->where('expiration_date', '<', date('Y-m-d'))->get();
    }
}