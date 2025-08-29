<?php namespace Tobuli\Repositories\Geofence;

use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Geofence as Entity;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Repositories\EloquentRepository;

class EloquentGeofenceRepository extends EloquentRepository implements GeofenceRepositoryInterface {

    public function __construct( Entity $entity )
    {
        $this->entity = $entity;
    }

    public function whereUserId($user_id)
    {
        return $this->entity->where('user_id', $user_id)->get();
    }

    public function create($data)
    {
        if (empty($data['polygon']))
            $data['polygon'] = [];

        if (!is_array($data['polygon']))
            $data['polygon'] = json_decode($data['polygon'], TRUE);

        if (isset($data['center']) && !is_array($data['center']))
            $data['center'] = json_decode($data['center'], TRUE);

        $polygon = [];
        foreach ($data['polygon'] as $poly) {
            array_push($polygon, ['lat' => floatval($poly['lat']), 'lng' => floatval($poly['lng'])]);
        }

        $coordinates = json_encode($polygon);

        if (empty($coordinates))
            throw new ValidationException([
                'polygon' => str_replace(':attribute', trans('front.polygon'), trans('validation.required'))
            ]);

        $cor_text = gen_polygon_text($polygon);
        $item = $this->entity->create([
            'active'        => (isset($data['active']) ? $data['active'] : 1),
            'user_id'       => $data['user_id'],
            'name'          => $data['name'],
            'group_id'      => (!isset($data['group_id']) || $data['group_id'] == 0 ? NULL : $data['group_id']),
            'radius'        => (!isset($data['radius']) ? NULL : $data['radius']),
            'center'        => (!isset($data['center']) ? NULL : $data['center']),
            'type'          => (!isset($data['type']) ? 'polygon' : $data['type']),
            'device_id'     => (!isset($data['device_id']) ? NULL : $data['device_id']),
            'polygon_color' => $data['polygon_color'],
        ]);

        DB::unprepared("UPDATE geofences SET coordinates = '".$coordinates."', polygon = PolygonFromText('POLYGON(({$cor_text}))') WHERE id = '{$item->id}'");

        return $item;
    }

    public function updateWithPolygon($id, $data) {
        if (empty($data['polygon']))
            $data['polygon'] = [];

        if (!is_array($data['polygon']))
            $data['polygon'] = json_decode($data['polygon'], TRUE);

        if (isset($data['center']) && !is_array($data['center']))
            $data['center'] = json_decode($data['center'], TRUE);

        $polygon = [];
        foreach ($data['polygon'] as $poly) {
            array_push($polygon, ['lat' => floatval($poly['lat']), 'lng' => floatval($poly['lng'])]);
        }

        $coordinates = json_encode($polygon);
        $cor_text = gen_polygon_text($data['polygon']);

        $this->entity->where('id', $id)->update([
                'name'          => $data['name'],
                'radius'        => (!isset($data['radius']) ? NULL : $data['radius']),
                'center'        => (!isset($data['center']) ? NULL : json_encode($data['center'])),
                'type'          => (!isset($data['type']) ? 'polygon' : $data['type']),
                'polygon_color' => $data['polygon_color'],
        ]
            + (array_key_exists('group_id', $data) ? ['group_id' => (empty($data['group_id']) ? NULL : $data['group_id'])] : [])
            + (array_key_exists('device_id', $data) ? ['device_id' => (empty($data['device_id']) ? NULL : $data['device_id'])] : [])
        );

        DB::unprepared("UPDATE geofences SET coordinates = '".$coordinates."', polygon = PolygonFromText('POLYGON(({$cor_text}))') WHERE id = '{$id}'");
    }
}