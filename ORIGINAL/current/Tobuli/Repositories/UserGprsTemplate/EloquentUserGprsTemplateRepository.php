<?php namespace Tobuli\Repositories\UserGprsTemplate;

use Tobuli\Entities\UserGprsTemplate as Entity;
use Tobuli\Repositories\EloquentRepository;

class EloquentUserGprsTemplateRepository extends EloquentRepository implements UserGprsTemplateRepositoryInterface {

    public function __construct( Entity $entity )
    {
        $this->entity = $entity;
    }

    public function getUserTemplatesByProtocol($user_id, $protocol)
    {
        $query = $this->entity->where('user_id', $user_id)->orderBy('title', 'asc');

        if ($protocol) {

            $query->where(function($q) use ($protocol){
                $q->whereNull('protocol');
                if (is_array($protocol))
                    $q->orWhereIn('protocol', array_unique($protocol));
                else
                    $q->orWhere('protocol', $protocol);
            });
        }

        return $query->get();
    }

    public function getUserTemplatesByDevices($user_id, $devices)
    {
        $query = $this->entity->where('user_id', $user_id)->orderBy('title', 'asc');

        $query->where(function($q) use ($devices) {
            $q->whereNull('adapted');

            $protocols = $devices->pluck('protocol')->all();
            $q->orWhereIn('protocol', array_unique($protocols));

            $ids = $devices->pluck('id')->all();
            $q->orWhereHas('devices', function($q2) use ($ids) {
                $q2->whereIn('id', $ids);
            });
        });

        $query->with('devices');

        return $query->get();
    }
}