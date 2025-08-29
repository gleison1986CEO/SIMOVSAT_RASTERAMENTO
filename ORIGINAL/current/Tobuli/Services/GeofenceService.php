<?php

namespace Tobuli\Services;


use Tobuli\Entities\Geofence;

class GeofenceService
{
    /**
     * @param Geofence $geofence
     * @param $point
     * @return Geofence
     */
    public function moveTo(Geofence $geofence, $point)
    {
        if ($geofence->type == Geofence::TYPE_CIRCLE)
            return $this->moveCircleTo($geofence, $point);
        else
            return $this->movePolygonTo($geofence, $point);
    }

    /**
     * @param Geofence $geofence
     * @param $point
     * @return Geofence
     */
    public function moveCircleTo(Geofence $geofence, $point)
    {
        $geofence->center = [
            'lat' => $point['lat'],
            'lng' => $point['lng']
        ];

        return $geofence;
    }

    /**
     * @param Geofence $geofence
     * @param $point
     * @return Geofence
     */
    public function movePolygonTo(Geofence $geofence, $point)
    {
        $newCoordinates = [];

        $center = $geofence->getCenter();

        if (is_null($center))
            return $geofence;

        $coordinates = json_decode($geofence->coordinates, true);

        foreach ($coordinates as $coordinate) {
            $newCoordinates[] = [
                'lat' => $coordinate['lat'] - $center['lat'],
                'lng' => $coordinate['lng'] - $center['lng'],
            ];
        }

        array_walk($newCoordinates, function(&$coordinate, $key) use ($point) {
            $coordinate = [
                'lat' => $coordinate['lat'] + $point['lat'],
                'lng' => $coordinate['lng'] + $point['lng'],
            ];
        });

        $geofence->coordinates = json_encode($newCoordinates);

        return $geofence;
    }
}