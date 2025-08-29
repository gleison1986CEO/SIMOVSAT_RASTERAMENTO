<?php namespace Tobuli\Repositories\ApnConfig;

use Tobuli\Entities\ApnConfig as Entity;
use Tobuli\Repositories\EloquentRepository;

class EloquentApnConfigRepository extends EloquentRepository implements ApnConfigRepositoryInterface {

    public function __construct( Entity $entity )
    {
        $this->entity = $entity;
        $this->searchable = [
            'apn_config.name',
            'apn_config.apn_name',
        ];
    }
}
