<?php namespace Tobuli\Repositories\DeviceConfig;

use Illuminate\Support\Facades\DB;
use Tobuli\Entities\DeviceConfig as Entity;
use Tobuli\Repositories\EloquentRepository;

class EloquentDeviceConfigRepository extends EloquentRepository implements DeviceConfigRepositoryInterface {

    public function __construct( Entity $entity )
    {
        $this->entity = $entity;
        $this->searchable = [
            'device_config.brand',
            'device_config.model',
        ];
    }
}
