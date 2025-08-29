<?php namespace Tobuli\Entities;

use Auth;
use Eloquent;
use Formatter;
use ModalHelpers\AlertModalHelper;
use Tobuli\Traits\SentCommandActor;

class Alert extends Eloquent
{
    use SentCommandActor;

	protected $table = 'alerts';

    protected $fillable = array(
        'active',
        'user_id',
        'type',
        'name',
        'schedules',
        'notifications',

        'zone',
        'schedule',
        'overspeed',
        'idle_duration',
        'ignition_duration',
        'pre_start_checklist_only',
        'stop_duration',
        'time_duration',
        'offline_duration',
        'distance',
        'period',
        'distance_tolerance',
        'continuous_duration',
        'command',
        'authorized'
    );

    protected $casts = [
        'data' => 'array',
        'notifications' => 'array',
    ];

    protected $appends = [
        'zone',
        'schedule',
        'command'
    ];

    protected $hidden = [
        'data'
    ];

    protected $properties = [
        'schedule' => 0,
        'command' => null,
        'zone' => 0,
        'idle_duration' => 0,
        'ignition_duration' => 0,
        'pre_start_checklist_only' => 0,
        'stop_duration' => 0,
        'time_duration' => 0,
        'offline_duration' => 0,
        'period' => 0,
        'distance_tolerance' => 0,
        'continuous_duration' => 0,
        'authorized' => 0,
    ];

    public function user() {
        return $this->belongsTo('Tobuli\Entities\User', 'user_id', 'id');
    }

    public function devices() {
        return $this->belongsToMany('Tobuli\Entities\Device')
            // escape deattached users devices
            ->join('alerts', 'alerts.id', '=', 'alert_device.alert_id')
            ->join('user_device_pivot', function ($join) {
                $join
                    ->on('user_device_pivot.device_id', '=', 'alert_device.device_id')
                    ->on('user_device_pivot.user_id', '=', 'alerts.user_id');
            })
            ->withPivot('started_at', 'fired_at',  'active_from', 'active_to');
    }

    public function geofences() {
        return $this->belongsToMany('Tobuli\Entities\Geofence');
    }

    public function pois() {
        return $this->belongsToMany('Tobuli\Entities\Poi');
    }

    public function zones() {
        return $this->belongsToMany('Tobuli\Entities\Geofence', 'alert_zone', 'alert_id', 'geofence_id');
    }

    public function fuel_consumptions() {
        return $this->hasMany('Tobuli\Entities\AlertFuelConsumption', 'alert_id');
    }

    public function drivers() {
        return $this->belongsToMany('Tobuli\Entities\UserDriver', 'alert_driver_pivot', 'alert_id', 'driver_id');
    }

    public function events_custom() {
        return $this->belongsToMany('Tobuli\Entities\EventCustom', 'alert_event_pivot', 'alert_id', 'event_id');
    }

    public function events() {
        return $this->hasMany('Tobuli\Entities\Event', 'alert_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('alerts.active', 1);
    }

    public function scopeCheckByPosition($query)
    {
        return $query->whereIn('type', [
            'custom',
            'overspeed',
            'driver',
            'driver_unauthorized',
            'geofence_in',
            'geofence_out',
            'geofence_inout',
            'sos',
            'fuel_change',
            'move_start'
        ]);
    }

    public function scopeCheckByTime($query)
    {
        return $query->whereIn('type', [
            'idle_duration',
            'ignition_duration',
            'stop_duration',
            'time_duration',
            'offline_duration',
            'distance',
            'poi_stop_duration',
            'poi_idle_duration',
        ]);
    }

    public function isActive()
    {
        if (!$this->active)
            return false;

        $activeFrom = $this->pivot->active_from ?? null;

        if ($activeFrom && strtotime($activeFrom) > time())
            return false;

        $activeTo = $this->pivot->active_to ?? null;

        if ($activeTo && strtotime($activeTo) < time())
            return false;

        return true;
    }

    public function getTypeTitleAttribute()
    {
        static $types = null;

        if (is_null($types)) {
            $types = AlertModalHelper::getTypes();
        }

        $type = array_first($types, function ($value, $key) {
            return $value['type'] == $this->type;
        });

        return $type['title'] ?? $this->type;
    }

    public function getChannelsAttribute()
    {
        $notifications = $this->notifications;

        $channels = [
            'push'         => array_get($notifications, 'push.active'),
            'email'        => array_get($notifications, 'email.active') ? array_get($notifications, 'email.input') : null,
            'mobile_phone' => array_get($notifications, 'sms.active') ? array_get($notifications, 'sms.input') : null,
            'webhook'      => array_get($notifications, 'webhook.active') ? array_get($notifications, 'webhook.input') : null,
            'command'      => array_get($this->command, 'active') ? $this->command : null,
        ];

        if (settings('plugins.alert_sharing.status')) {
            if (array_get($notifications, 'sharing_email.active'))
                $channels = array_merge($channels, [
                    'sharing_email' => array_get($notifications, 'sharing_email.input')
                ]);

            if (array_get($notifications, 'sharing_sms.active'))
                $channels = array_merge($channels, [
                    'sharing_sms' => array_get($notifications, 'sharing_sms.input')
                ]);
        }

        return $channels;
    }

    public function getSchedulesAttribute()
    {
        $schedules = $this->getSchedulesUTC();

        return $this->convertSchedules($schedules, false);
    }

    public function getSchedulesUTC()
    {
        return json_decode($this->attributes['schedules'], true);
    }

    public function setSchedulesAttribute($schedules)
    {
        $schedules = $this->convertSchedules($schedules, true);

        $this->attributes['schedules'] = json_encode($schedules);
    }

    public function getOverspeed()
    {
        return array_get($this->data, 'overspeed', 0);
    }

    public function getOverspeedAttribute()
    {
        return Formatter::speed()->format( $this->getOverspeed() );
    }

    public function setOverspeedAttribute($value)
    {
        $value = round(Formatter::speed()->reverse($value), 0);

        $this->setProperty('overspeed', $value);
    }

    public function getDistance()
    {
        return array_get($this->data, 'distance', 0);
    }

    public function getDistanceAttribute()
    {
        return Formatter::distance()->format( $this->getDistance()  );
    }

    public function setDistanceAttribute($value)
    {
        $value = round(Formatter::distance()->reverse($value), 3);

        $this->setProperty('distance', $value);
    }

    public function getDistanceTolerance()
    {
        return array_get($this->data, 'distance_tolerance', 0);
    }

    protected function hasProperty($key)
    {
        return array_key_exists($key, $this->properties);
    }

    protected function getProperty($key)
    {
        return array_get($this->data, $key, $this->properties[$key]);
    }

    protected function setProperty($key, $value)
    {
        $data = $this->data ?? [];

        if ($value)
            array_set($data, $key, $value);
        else
            array_forget($data, $key);

        $this->data = $data;
    }

    protected function mutateAttribute($key, $value)
    {
        if ($this->hasProperty($key))
            return $this->getProperty($key);

        return parent::mutateAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        if ($this->hasProperty($key))
            return $this->getProperty($key);

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($this->hasProperty($key)) {
            $this->setProperty($key, $value);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    private function convertSchedules($schedules, $reverse = false)
    {
        if (empty($schedules))
            return null;

        if ( ! (Auth::check() && Auth::user()->timezone_id != 57))
            return $schedules;

        $result = [];

        foreach($schedules as $weekday => $times) {
            foreach ($times as $time) {
                $_time = strtotime($weekday . ' ' . $time);

                if ($reverse) {
                    $_time = Formatter::time()->reverse(date('Y-m-d H:i:s', $_time), 'l H:i');
                } else {
                    $_time = Formatter::time()->convert(date('Y-m-d H:i:s', $_time), 'l H:i');
                }

                list($_weekday, $_time) = explode(' ', $_time);

                $_weekday = strtolower($_weekday);

                $result[$_weekday][] = $_time;
            }
        }

        return $result;
    }
}
