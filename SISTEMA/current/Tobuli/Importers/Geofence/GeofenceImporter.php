<?php

namespace Tobuli\Importers\Geofence;

use CustomFacades\Repositories\GeofenceRepo;
use Illuminate\Support\Facades\Cache;
use Tobuli\Entities\GeofenceGroup;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\Importer;

class GeofenceImporter extends Importer
{
    protected $defaults = [
        'active'        => 1,
        'type'          => 'polygon',
        'polygon_color' => '#ffffff',
    ];

    protected function getDefaults()
    {
        return $this->defaults;
    }

    protected function importItem($data, $attributes = [])
    {
        $data = $this->mergeDefaults($data);
        $data = $this->setUser($data, $attributes);

        if ( ! $this->validate($data)) {
            return;
        }

        $this->normalize($data);

        if ($this->getGeofence($data)) {
            return;
        }

        $this->create($data);
    }

    private function normalize(array &$data): array
    {
        $last_point = last($data['polygon']);
        $first_point = head($data['polygon']);

        if ($last_point != $first_point) {
            array_push($data['polygon'], $first_point);
        }

        $data['type'] = strtolower($data['type']);

        unset($data['group_id']);

        return $data;
    }

    private function getGeofence($data)
    {
        return GeofenceRepo::first(array_only($data, ['user_id', 'name', 'type']));
    }

    private function create($data)
    {
        beginTransaction();
        try {
            if ( ! empty($data['group'])) {
                $this->createGroup($data);
            }

            GeofenceRepo::create($data);
        } catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }
        commitTransaction();
    }

    private function createGroup(& $data)
    {
        $key = md5("{$data['user_id']}.{$data['group']}");

        $data['group_id'] = Cache::store('array')->rememberForever($key, function() use ($data) {
            $group =  GeofenceGroup::firstOrCreate([
                'title'   => $data['group'],
                'user_id' => $data['user_id']
            ]);

            return $group->id;
        });

        unset($data['group']);
    }

    public static function getValidationRules(): array
    {
        return [
            'name'          => 'required',
            'type'          => 'required|in:polygon,circle',
            'polygon'       => 'nullable|required_if:type,polygon|array',
            'polygon.*.lat' => 'lat',
            'polygon.*.lng' => 'lng',
            'radius'        => 'nullable|required_if:type,circle|numeric',
            'center'        => 'nullable|required_if:type,circle',
            'center.lat'    => 'lat',
            'center.lng'    => 'lng',
        ];
    }
}
