<?php namespace Tobuli\Entities;

use Eloquent;

use Tobuli\Helpers\PolygonHelper;
use Tobuli\Traits\Filterable;
use Tobuli\Traits\Searchable;

class Geofence extends Eloquent {

    use Searchable, Filterable;

    const TYPE_CIRCLE = 'circle';
    const TYPE_POLYGON = 'polygon';

	protected $table = 'geofences';

    protected $fillable = array('user_id', 'group_id', 'device_id', 'name', 'active', 'polygon_color', 'type', 'radius', 'center');

    protected $hidden = array('polygon');

    protected $casts = [
        'center' => 'array',
        'radius' => 'float'
    ];

    protected $searchable = [
        'name',
    ];

    protected $filterables = [
        'group_id',
    ];

    protected $polygonHelpers = [];

    public function user() {
        return $this->belongsTo('Tobuli\Entities\User', 'user_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(GeofenceGroup::class, 'group_id');
    }

    public function getGroupIdAttribute($value)
    {
        if (is_null($value))
            return 0;

        return $value;
    }

    public function setGroupIdAttribute($value)
    {
        if (empty($value))
            $value = null;

        $this->attributes['group_id'] = $value;
    }

    public function pointIn($data)
    {
        if (is_object($data)) {
            $point = [
                'lat' => $data->latitude,
                'lng' => $data->longitude
            ];
        } elseif (is_array($data)) {
            $point = [
                'lat' => $data['latitude'],
                'lng' => $data['longitude']
            ];
        } elseif (is_string($data)) {
            $coordinates = explode(" ", $data);
            $point = [
                'lat' => $coordinates[0],
                'lng' => $coordinates[1]
            ];
        } else {
            return null;
        }

        if ($this->type == 'circle')
            return $this->pointInCircle($point);

        return $this->pointInPolygon($point);
    }

    public function pointOut($data)
    {
        return ! $this->pointIn($data);
    }

    /**
     * @param $point ['latitude' => x, 'longitude' => y]
     * @return float|int
     */
    public function pointAwayBy($point)
    {
        if ($this->pointIn($point))
            return 0;

        $center = $this->getCenter();

        return getDistance($center['lat'], $center['lng'], $point['latitude'], $point['longitude']);
    }

    public function getCenter()
    {
        if ($this->type == self::TYPE_CIRCLE)
            return $this->center;

        return $this->getPolygonHelper()->getCenter();
    }

    private function pointInPolygon($point)
    {
        return false !== $this->getPolygonHelper()->pointInPolygon($point);
    }

    private function pointInCircle($point)
    {
        $center = $this->center;

        return $this->radius > (getDistance($center['lat'], $center['lng'], $point['lat'], $point['lng']) * 1000);
    }

    /**
     * @return PolygonHelper
     */
    private function getPolygonHelper()
    {
        if (!isset($this->polygonHelpers[$this->id]))
        {
            $coordinates = json_decode($this->coordinates, TRUE);

            if (empty($coordinates)) {
                $coordinates = [];
            } else {
                $first = current($coordinates);
                array_push($coordinates, $first);
            }

            $this->polygonHelpers[$this->id] = new PolygonHelper($coordinates);
        }

        return $this->polygonHelpers[$this->id];
    }
}
