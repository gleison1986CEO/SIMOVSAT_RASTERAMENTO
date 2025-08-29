<?php

namespace App\Transformers\Geofence;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Geofence;

class GeofenceMapTransformer extends GeofenceTransformer {

    protected $defaultIncludes = [];

    /**
     * @param Geofence $entity
     * @return array|null
     */
    public function transform($entity)
    {
        if (! $entity) {
            return null;
        }

        return [
            'id'            => (int) $entity->id,
            'group_id'      => (int) $entity->group_id ?? 0,
            'device_id'     => (int) $entity->device_id ?? null,
            'active'        => (int) $entity->active,
            'name'          => (string) $entity->name,
            'polygon_color' => (string) $entity->polygon_color,
            'type'          => (string) $entity->type,
            'center'        => $entity->center,
            'radius'        => (float) $entity->radius,
            //'description' => (string) $entity->description,

            'coordinates' => json_decode($entity->coordinates, true),
        ];
    }
}
