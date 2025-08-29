<?php

namespace Tobuli\Entities;

use App\Events\DeviceSubscriptionActivate;
use App\Events\DeviceSubscriptionRenew;
use App\Jobs\TrackerConfigWithRestart;
use Carbon\Carbon;
use CustomFacades\Repositories\TraccarDeviceRepo;
use Eloquent;
use Formatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\Chattable;
use Tobuli\Traits\Customizable;
use Tobuli\Traits\EventLoggable;
use Tobuli\Traits\FcmTokensTrait;
use Tobuli\Traits\Filterable;
use Tobuli\Traits\Includable;
use Tobuli\Traits\Nameable;
use Tobuli\Traits\Searchable;

class Device extends Eloquent implements FcmTokenableInterface
{
    use Chattable, EventLoggable, Searchable, Filterable, Includable, Nameable, Customizable, FcmTokensTrait;

    const STATUS_ACK     = 'ack';
    const STATUS_OFFLINE = 'offline';
    const STATUS_ONLINE  = 'online';
    const STATUS_ENGINE  = 'engine';

    const IMAGE_PATH     = 'images/device_images/';

    protected $table = 'devices';

    protected $fillable = array(
        'deleted',
        'traccar_device_id',
        'timezone_id',
        'name',
        'imei',
        'icon_id',
        'fuel_measurement_id',
        'fuel_quantity',
        'fuel_price',
        'fuel_per_km',
        'sim_number',
        'device_model',
        'plate_number',
        'vin',
        'registration_number',
        'object_owner',
        'tabela_fipe',
        'additional_notes',
        'expiration_date',
        'tail_color',
        'tail_length',
        'engine_hours',
        'detect_engine',
        'min_moving_speed',
        'min_fuel_fillings',
        'min_fuel_thefts',
        'snap_to_road',
        'gprs_templates_only',
        'valid_by_avg_speed',
        'icon_colors',
        'parameters',
        'currents',
        'active',
        'forward',
        'sim_activation_date',
        'sim_expiration_date',
        'installation_date',
        'msisdn',
        'device_type_id',
        'app_tracker_login',
    );

    protected $appends = [
        'stop_duration'
        //'lat',
        //'lng',
        //'speed',
        //'course',
        //'altitude',
        //'protocol',
        //'time'
    ];

    //protected $hidden = ['currents'];

    protected $casts = [
        'currents' => 'array'
    ];

    protected $searchable = [
        'name',
        'imei',
        'sim_number',
        'vin',
        'plate_number',
        'registration_number',
        'object_owner',
        'tabela_fipe',
        'device_model',
        'additional_notes'
    ];
    protected $filterables = [
        'id',
        'imei',
        'sim_number',
    ];

    protected $hidden = ['app_uuid'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            $traccar_item = TraccarDeviceRepo::create([
                'name' => $device->name,
                'uniqueId' => $device->imei,
                'database_id' => Database::getActiveDatabaseId()
            ]);

            $device->traccar_device_id = $traccar_item->id;
        });

        static::updated(function ($device) {
            TraccarDeviceRepo::update($device->traccar_device_id, [
                'name' => $device->name,
                'uniqueId' => $device->imei
            ]);
        });

        static::saved(function ($device) {
            if ($device->isDirty('imei'))
                UnregisteredDevice::where('imei', $device->imei)->delete();

            if ($device->isDirty('forward'))
                dispatch((new TrackerConfigWithRestart()));
        });
    }

    public function positions()
    {
        return $this->traccar->positions();
    }

    public function positionTraccar()
    {
        if ( ! $this->traccar) {
            return null;
        }

        return new \Tobuli\Entities\TraccarPosition([
            'id' => $this->traccar->lastestPosition_id,
            'device_id' => $this->traccar->id,
            'latitude' => $this->traccar->lastValidLatitude,
            'longitude' => $this->traccar->lastValidLongitude,
            'other' => $this->traccar->other,
            'speed' => $this->traccar->speed,
            'altitude' => $this->traccar->altitude,
            'course' => $this->traccar->course,
            'time' => $this->traccar->time,
            'device_time' => $this->traccar->device_time,
            'server_time' => $this->traccar->server_time,
            'protocol' => $this->traccar->protocol,
            'valid' => true
        ]);
    }

    public function createPositionsTable()
    {
        $connection = $this->positions()->getRelated()->getConnectionName();
        $tableName = $this->positions()->getRelated()->getTable();

        if (Schema::connection($connection)->hasTable($tableName))
            throw new \Exception(trans('global.cant_create_device_database'));

        Schema::connection($connection)->create($tableName, function(Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->bigInteger('device_id')->unsigned()->index();
            $table->double('altitude')->nullable();
            $table->double('course')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->text('other')->nullable();
            $table->double('power')->nullable();
            $table->double('speed')->nullable()->index();
            $table->datetime('time')->nullable()->index();
            $table->datetime('device_time')->nullable();
            $table->datetime('server_time')->nullable()->index();
            $table->text('sensors_values')->nullable();
            $table->tinyInteger('valid')->nullable();
            $table->double('distance')->nullable();
            $table->string('protocol', 20)->nullable();
        });
    }

    public function getDatabaseName()
    {
        return $this->traccar ? $this->traccar->getDatabaseName() : 'traccar_mysql';
    }

    public function icon()
    {
        return $this->hasOne('Tobuli\Entities\DeviceIcon', 'id', 'icon_id');
    }

    public function getIconAttribute()
    {
        $icon = $this->getRelationValue('icon');

        return $icon ? $icon->setStatus($this->getStatus()) : null;
    }

    public function traccar()
    {
        return $this->hasOne('Tobuli\Entities\TraccarDevice', 'id', 'traccar_device_id');
    }

    public function alerts()
    {
        return $this->belongsToMany('Tobuli\Entities\Alert', 'alert_device', 'device_id', 'alert_id')
            // escape deattached users devices
            ->join('user_device_pivot', function ($join) {
                $join
                    ->on('user_device_pivot.device_id', '=', 'alert_device.device_id')
                    ->on('user_device_pivot.user_id', '=', 'alerts.user_id');
            })
            ->withPivot('started_at', 'fired_at', 'active_from', 'active_to');
    }

    public function events()
    {
        return $this->hasMany('Tobuli\Entities\Event', 'device_id');
    }

    public function last_event()
    {
        $query = $this->hasOne('Tobuli\Entities\Event', 'device_id');

        if ($user = getActingUser()) {
            $query->where('user_id', $user->id);
        }

        return $query->orderBy('id', 'desc');
    }

    public function users() {
        return $this->belongsToMany('Tobuli\Entities\User', 'user_device_pivot', 'device_id', 'user_id')->withPivot('group_id', 'current_driver_id', 'current_events');
    }

    public function driver() {
        //return $this->belongsToMany('Tobuli\Entities\UserDriver', 'user_device_pivot', 'device_id', 'current_driver_id');
        return $this->hasOne('Tobuli\Entities\UserDriver', 'id', 'current_driver_id');
    }

    public function drivers() {
        return $this->hasMany('Tobuli\Entities\UserDriver', 'device_id');
    }

    public function sensors() {
        return $this->hasMany('Tobuli\Entities\DeviceSensor', 'device_id');
    }

    public function services() {
        return $this->hasMany('Tobuli\Entities\DeviceService', 'device_id');
    }

    public function expenses()
    {
        return $this->hasMany('Tobuli\Entities\DeviceExpense', 'device_id');
    }

    public function timezone()
    {
        return $this->hasOne('Tobuli\Entities\Timezone', 'id', 'timezone_id');
    }

    public function deviceCameras() {
        return $this->hasMany('Tobuli\Entities\DeviceCamera', 'device_id');
    }

    public function group()
    {
        return $this->hasOne('Tobuli\Entities\DeviceGroup', 'id', 'group_id');
    }

    public function plans()
    {
        return $this->belongsToMany('Tobuli\Entities\DevicePlan', 'device_device_plan', 'device_id', 'plan_id');
    }

    public function deviceType()
    {
        return $this->hasOne('Tobuli\Entities\DeviceType', 'id', 'device_type_id');
    }

    public function subscriptions()
    {
        return $this->hasManyThrough(
            'Tobuli\Entities\Subscription',
            'Tobuli\Entities\Order',
            'entity_id',
            'order_id',
            'id',
            'id'
        )->where('orders.entity_type', 'device')->active();
    }

    public function sentCommands()
    {
        return $this->belongsTo(SentCommand::class, 'imei', 'device_imei');
    }

    public function setTimezoneIdAttribute($value)
    {
        $this->attributes['timezone_id'] = empty($value) ? null : $value;
    }

    public function setIconColorsAttribute($value)
    {
        $this->attributes['icon_colors'] = json_encode($value);
    }

    public function getIconColorsAttribute($value)
    {
        return json_decode($value, TRUE);
    }

    public function setForwardAttribute($value)
    {
        if (array_get($value, 'active'))
            $this->attributes['forward'] = json_encode($value);
        else
            $this->attributes['forward'] = null;
    }

    public function getForwardAttribute($value)
    {
        return json_decode($value, TRUE);
    }

    public function isExpired()
    {
        if ( ! $this->hasExpireDate())
            return false;

        return  strtotime($this->expiration_date) < time();
    }

    public function hasExpireDate()
    {
        if ( ! $this->expiration_date)
            return false;

        if ($this->expiration_date == '0000-00-00')
            return false;

        if ($this->expiration_date == '0000-00-00 00:00:00')
            return false;

        return true;
    }

    public function isPlanAble()
    {
        if (!settings('main_settings.enable_device_plans'))
            return false;

        if (!$this->hasExpireDate())
            return false;

        return true;
    }

    public function isConnected()
    {
        return Redis::connection('process')->get('connected.' . $this->imei) ? true : false;
    }

    public function getParameter($key, $default = null)
    {
        $parameters = $this->getParameters();

        return array_key_exists($key, $parameters) ? $parameters[$key] : $default;
    }

    public function setParameter($key, $value)
    {
        $parameters = $this->getParameters();

        $parameters[$key] = $value;

        $this->setParameters($parameters);
    }

    public function setParameters($value)
    {
        if ( is_array($value))
        {
            $xml = '<info>';

            foreach ($value as $key => $val)
            {
                if (is_numeric($key)) continue;
                if (is_array($val)) continue;

                $val = is_bool($val) ? ($val ? 'true' : 'false') : $val;
                $val = html_entity_decode($val);
                $xml .= "<{$key}>{$val}</$key>";
            }
            $xml .= '</info>';

            $value = $xml;
        }

        $this->traccar->other = $value;
    }

    public function getParameters()
    {
        if ( ! isset($this->traccar->other))
            return [];

        $parameters = parseXMLToArray($this->traccar->other);

        return $parameters;
    }

    public function getSumDistance($from = null, $to = null)
    {
        $query = $this->positions();

        if ($from)
            $query->where('time', '>', $from);

        if ($to)
            $query->where('time', '<', $to);

        return $query->sum('distance');
    }

    public function getTotalDistance()
    {
        $distance = $this->getParameter('totaldistance') / 1000;

        return Formatter::distance()->format($distance);
    }

    public function getSpeed() {
        $speed = 0;

        if (isset($this->traccar->speed) && $this->getStatus() == 'online')
            $speed = $this->traccar->speed;

        return $speed;
    }

    public function getTimeoutStatus()
    {
        static $minutes = null;

        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout') * 60;

        $ackTime = strtotime($this->getAckTime());
        $serverTime = strtotime($this->getServerTime());

        if (max($ackTime, $serverTime) < time() - $minutes)
            return self::STATUS_OFFLINE;

        return $ackTime > $serverTime ? self::STATUS_ACK : self::STATUS_ONLINE;
    }

    public function getStatusAttribute() {
        return $this->getStatus();
    }

    public function getStatus()
    {
        if ($this->isExpired())
            return self::STATUS_OFFLINE;

        $status = $this->getTimeoutStatus();

        if ($status != self::STATUS_ONLINE)
            return $status;

        $speed  = isset($this->traccar->speed) ? $this->traccar->speed : null;

        if ($speed >= $this->min_moving_speed)
            return self::STATUS_ONLINE;

        $stopDuration = $this->getStopDuration();

        if (is_null($stopDuration))
            return self::STATUS_ACK;

        if ($stopDuration < 10)
            return self::STATUS_ONLINE;

        if (null !== $engine = $this->getEngineStatus()) {
            return $engine ? self::STATUS_ENGINE : self::STATUS_ACK;
        }

        return self::STATUS_ACK;
    }

    public function getStatusColorAttribute() {
        return $this->getStatusColor();
    }

    public function getStatusColor($status = null)
    {
        if (is_null($status)) {
            $status = $this->getStatus();
        }

        switch ($status) {
            case 'online':
                $icon_status = 'moving';
                break;
            case 'ack':
                $icon_status = 'stopped';
                break;
            case 'engine':
                $icon_status = 'engine';
                break;
            default:
                $icon_status = 'offline';
        }

        return array_get($this->icon_colors, $icon_status, 'red');
    }

    public function getSensorsByType($type)
    {
        $sensors = $this->sensors;

        if (empty($this->sensors))
            return null;

        return $this->sensors->filter(function ($sensor) use ($type) {
            return $sensor->type == $type;
        });
    }

    public function getSensorByType($type)
    {
        $sensors = $this->sensors;

        if (empty($sensors))
            return null;

        foreach ($sensors as $sensor) {
            if ($sensor['type'] == $type) {
                $type_sensor = $sensor;
                break;
            }
        }

        if (empty($type_sensor))
            return null;

        return $type_sensor;
    }

    public function getRfidSensor()
    {
        return $this->getSensorByType('rfid');
    }

    public function getFuelTankSensor()
    {
        $sensor = $this->getSensorByType('fuel_tank');

        if ($sensor)
            return $sensor;

        return $this->getSensorByType('fuel_tank_calibration');
    }

    public function getLoadSensor()
    {
        $sensor = $this->getSensorByType('load');

        if ($sensor) {
            return $sensor;
        }

        return $this->getSensorByType('load_calibration');
    }

    public function getOdometerSensor()
    {
        return $this->getSensorByType('odometer');
    }

    public function getEngineHoursSensor()
    {
        return $this->getSensorByType('engine_hours');
    }

    public function getEngineSensor()
    {
        $detect_engine = $this->engine_hours == 'engine_hours' ? $this->detect_engine : $this->engine_hours;

        if (empty($detect_engine))
            return null;

        if ($detect_engine == 'gps')
            return null;

        return $this->getSensorByType($detect_engine);
    }

    public function getEngineStatusAttribute()
    {
        return $this->getEngineStatus();
    }

    public function getEngineStatus($formated = false)
    {
        $sensor = $this->getEngineSensor();

        if (empty($sensor))
            return $formated ? '-' : null;

        if ($this->getTimeoutStatus() == self::STATUS_OFFLINE)
            return false;

        $value = $sensor->getValueCurrent($this->other);

        return $formated ? $sensor->formatValue($value) : (bool)$value;
    }

    public function getEngineStatusFrom($date_from) {
        $sensor = $this->getEngineSensor();

        if (empty($sensor))
            return false;

        $position = $this->positions()->where('time', '<=', $date_from)->first();

        if ( ! $position)
            return false;

        return $position->getSensorValue($sensor->id);
    }

    public function getDistanceBetween($dateFrom, $dateTo)
    {
        $odometer = $this->getOdometerSensor();

        $query = $this->positions()->whereBetween('time', [$dateFrom, $dateTo])->limit(1);

        if ( ! is_null($odometer) && $odometer->odometer_value_by != 'virtual_odometer') {
            $query->where('sensors_values', 'like', '%'.$odometer->id.'%');
        }

        $first     = (clone $query)->orderBy('time', 'asc');
        $positions = (clone $query)->orderBy('time', 'desc')->union($first)->get();

        if ($positions->count() < 2)
            return 0;

        if ( ! is_null($odometer) && $odometer->odometer_value_by != 'virtual_odometer') {
            $to = $odometer->getValuePosition($positions[0]);
            $from = $odometer->getValuePosition($positions[1]);

            $distance = (empty($from) || empty($to)) ? 0 : $to - $from;
        } else {
            $distance = $positions[0]->getParameter('totaldistance') - $positions[1]->getParameter('totaldistance');

            //from meters to kilometers
            $distance = $distance / 1000;
        }

        return ($distance > 0) ? $distance : 0;
    }

    public function getProtocol($user = null)
    {
        $user = $user ?? getActingUser();

        return ($this->protocol && $user->perm('device.protocol', 'view')) ? $this->protocol : null;
    }

    public function setProtocolAttribute($value)
    {
        $this->attributes['protocol'] = $value;
    }

    public function getProtocolAttribute()
    {
        if (array_key_exists('protocol', $this->attributes))
            return $this->attributes['protocol'];

        return isset($this->traccar->protocol) ? $this->traccar->protocol : null;
    }

    public function getDeviceTime()
    {
        return $this->traccar && $this->traccar->device_time ? $this->traccar->device_time : null;
    }

    public function getTime()
    {
        return $this->traccar && $this->traccar->time ? $this->traccar->time : null;
    }

    public function getAckTime()
    {
        return $this->traccar && $this->traccar->ack_time ? $this->traccar->ack_time : null;
    }

    public function getServerTime()
    {
        return $this->traccar && $this->traccar->server_time ? $this->traccar->server_time : null;
    }

    public function getTimeAttribute()
    {
        if ($this->isExpired())
            return trans('front.expired');

        $time = max($this->getTime(), $this->getAckTime());

        if (empty($time) || substr($time, 0, 4) == '0000')
            return trans('front.not_connected');

        return Formatter::time()->human($time);
    }

    public function getOnlineAttribute() {
        return $this->getStatus();
    }

    public function getLatAttribute()
    {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->lastValidLatitude) ? cord($this->traccar->lastValidLatitude) : null;
    }

    public function getLngAttribute()
    {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->lastValidLongitude) ? cord($this->traccar->lastValidLongitude) : null;
    }

    public function getLatitudeAttribute()
    {
        return isset($this->traccar->lastValidLatitude) ? cord($this->traccar->lastValidLatitude) : null;
    }

    public function getLongitudeAttribute()
    {
        return isset($this->traccar->lastValidLongitude) ? cord($this->traccar->lastValidLongitude) : null;
    }

    public function getCourseAttribute() {
        $course = 0;

        if (isset($this->traccar->course))
            $course = $this->traccar->course;

        return round($course);
    }

    public function getAltitudeAttribute() {
        $altitude = 0;

        if (isset($this->traccar->altitude))
            $altitude = $this->traccar->altitude;

        return Formatter::altitude()->format($altitude);
    }

    public function getTailAttribute() {
        $length = $this->tail_length;

        if (!$length)
            return [];

        if (empty($this->traccar->latest_positions))
            return [];

        $tail = [];
        $arr = explode(';',  $this->traccar->latest_positions);

        foreach ($arr as $value) {
            if ($length-- < 0)
                break;

            try {
                list($lat, $lng) = explode('/', $value);

                array_unshift($tail, [
                    'lat' => $lat,
                    'lng' => $lng
                ]);
            } catch (\Exception $e) {}
        }

        return $tail;
    }

    public function getLatestPositionsAttribute() {
        return isset($this->traccar->latest_positions) ? $this->traccar->latest_positions : null;
    }

    public function getTimestampAttribute() {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
    }

    public function getServerTimestampAttribute() {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
    }

    public function getAckTimestampAttribute() {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->ack_time) ? strtotime($this->traccar->ack_time) : 0;
    }

    public function getAckTimeAttribute() {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->ack_time) ? $this->traccar->ack_time : null;
    }

    public function getServerTimeAttribute() {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->server_time) ? $this->traccar->server_time : null;
    }

    public function getMovedAtAttribute() {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->moved_at) ? $this->traccar->moved_at : null;
    }

    public function getMovedTimestampAttribute() {
        return $this->moved_at ? strtotime($this->moved_at) : 0;
    }

    public function getLastConnectTimeAttribute() {
        $lastConnect = $this->getLastConnectTimestampAttribute();

        return $lastConnect ? date('Y-m-d H:i:s', $lastConnect) : null;
    }

    public function getLastConnectTimestampAttribute() {
        return max($this->server_timestamp, $this->ack_timestamp);
    }

    public function getOtherAttribute() {
        return isset($this->traccar->other) ? $this->traccar->other : null;
    }

    public function getSpeedAttribute() {
        return Formatter::speed()->format($this->getSpeed());
    }

    public function getIdleDuration()
    {
        $engine_off_at = isset($this->traccar->engine_off_at) ? strtotime($this->traccar->engine_off_at) : 0;
        $engine_on_at  = isset($this->traccar->engine_on_at) ? strtotime($this->traccar->engine_on_at) : 0;
        $moved_at      = isset($this->traccar->moved_at) ? strtotime($this->traccar->moved_at) : 0;
        $time          = isset($this->traccar->time) ? strtotime($this->traccar->time) : 0;
        $server_time   = isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
        $engine_changed_at = isset($this->traccar->engine_changed_at) ? strtotime($this->traccar->engine_changed_at) : 0;

        if ( ! $moved_at)
            return 0;

        if ( ! $engine_off_at)
            return 0;

        if ($engine_on_at < $engine_off_at)
            return 0;

        $check_at = max($engine_changed_at, $moved_at);

        //device send incorrcet self timestamp
        if ($server_time > $time)
            return time() - $check_at + ($time - $server_time);

        return time() - $check_at;
    }

    public function getIdleDurationAttribute()
    {
        $duration = $this->getIdleDuration();

        return Formatter::duration()->human($duration);
    }

    public function getIgnitionDuration()
    {
        $engineOn      = isset($this->traccar->engine_on_at) ? strtotime($this->traccar->engine_on_at) : 0;
        $engineChanged = isset($this->traccar->engine_changed_at) ? strtotime($this->traccar->engine_changed_at) : 0;
        $time          = isset($this->traccar->time) ? strtotime($this->traccar->time) : 0;
        $serverTime    = isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;

        if (! $engineOn || ! $engineChanged) {
            return 0;
        }

        if ($engineChanged >= $engineOn) {
            return 0;
        }

        //device sent incorrcet self timestamp
        if ($serverTime > $time) {
            return time() - $engineChanged + ($time - $serverTime);
        }

        return time() - $engineChanged;
    }

    public function getIgnitionDurationAttribute()
    {
        $duration = $this->getIgnitionDuration();

        return Formatter::duration()->human($duration);
    }

    public function getStopDuration()
    {
        $moved_at    = isset($this->traccar->moved_at) ? strtotime($this->traccar->moved_at) : 0;
        $time        = isset($this->traccar->time) ? strtotime($this->traccar->time) : 0;
        $server_time = isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
        $ack_time    = isset($this->traccar->ack_time) ? strtotime($this->traccar->ack_time) : 0;

        if ( ! $moved_at)
            return null;

        $last_time = max($server_time, $ack_time);

        //device send incorrect self timestamp
        if ($time > $last_time )
            return time() - $moved_at + ($time - $last_time);

        return time() - $moved_at;
    }

    public function getStopDurationAttribute()
    {
        $duration = $this->getStopDuration();

        if (is_null($duration))
            return '-';

        if ($duration < 5)
            $duration = 0;

        return Formatter::duration()->human($duration);
    }

    public function getFormatSensors()
    {
        if ($this->isExpired())
            return null;

        $result = [];

        foreach ($this->sensors as $sensor) {
            if (in_array($sensor->type, ['harsh_acceleration', 'harsh_breaking', 'harsh_turning']))
                continue;

            $value = $sensor->getValueCurrent($this->other);

            $result[] = [
                'id'            => $sensor->id,
                'type'          => $sensor->type,
                'name'          => $sensor->formatName(),
                'show_in_popup' => $sensor->show_in_popup,

                //'text'          => htmlentities( $sensor->formatValue($value) ),
                'value'         => htmlspecialchars($sensor->formatValue($value)),
                'val'           => $value,
                'scale_value'   => $sensor->getValueScale($value)
            ];
        }

        return $result;
    }

    public function getFormatServices()
    {
        if ($this->isExpired())
            return null;

        $result = [];

        foreach ($this->services as $service)
        {
            $service->setSensors($this->sensors);

            $result[] = [
                'id'       => $service->id,
                'name'     => $service->name,
                'value'    => $service->expiration(),
                'expiring' => $service->isExpiring()
            ];
        }

        return $result;
    }

    public function generateTail() {
        $limit = 15;

        $positions = $this->positions()
            ->where('distance', '>', 0.02)
            ->orderliness()
            ->limit($limit)
            ->get();

        $tail_positions = [];

        foreach ($positions as $position) {
            $tail_positions[] = $position->latitude.'/'.$position->longitude;
        }

        $this->traccar->update([
            'latest_positions' => implode(';', $tail_positions)
        ]);
    }

    public function applyPositionsTimezone()
    {
        if ( ! $this->timezone ) {
            $value = 'device_time';
        } elseif ( $this->timezone->id == 57) {
            $value = 'device_time';
        } else {
            list($hours, $minutes) = explode(' ', $this->timezone->time);

            if ($this->timezone->prefix == 'plus')
                $value = "DATE_ADD(device_time, INTERVAL '$hours:$minutes' HOUR_MINUTE)";
            else
                $value = "DATE_SUB(device_time, INTERVAL '$hours:$minutes' HOUR_MINUTE)";
        }

        $this->traccar()->update(['time' => DB::raw($value)]);
        $this->positions()->update(['time' => DB::raw($value)]);
    }

    public function isCorrectUTC()
    {
        $change = 900; //15 mins

        $ack_time    = strtotime( $this->getAckTime() );
        $server_time = strtotime( $this->getServerTime() );
        $device_time = strtotime( $this->getDeviceTime() );

        $last = max($ack_time, $server_time);

        if ($last && (abs($last - $device_time) < $change))
            return true;

        return false;
    }

    public function canChat()
    {
        $protocol = isset($this->traccar->protocol) ? $this->traccar->protocol : null;

        return $protocol == 'osmand';
    }

    public function scopeNPerGroup($query, $group, $n = 10)
    {
        // queried table
        $table = ($this->getTable());

        // initialize MySQL variables inline
        $query->from( DB::raw("(SELECT @rank:=0, @group:=0) as vars, {$table}") );

        // if no columns already selected, let's select *
        if ( ! $query->getQuery()->columns)
        {
            $query->select("{$table}.*");
        }

        // make sure column aliases are unique
        $groupAlias = 'group_'.md5(time());
        $rankAlias  = 'rank_'.md5(time());

        // apply mysql variables
        $query->addSelect(DB::raw(
            "@rank := IF(@group = {$group}, @rank+1, 1) as {$rankAlias}, @group := {$group} as {$groupAlias}"
        ));

        // make sure first order clause is the group order
        $query->getQuery()->orders = (array) $query->getQuery()->orders;
        array_unshift($query->getQuery()->orders, ['column' => $group, 'direction' => 'asc']);

        // prepare subquery
        $subQuery = $query->toSql();

        // prepare new main base Query\Builder
        $newBase = $this->newQuery()
            ->from(DB::raw("({$subQuery}) as {$table}"))
            ->mergeBindings($query->getQuery())
            ->where($rankAlias, '<=', $n)
            ->getQuery();

        // replace underlying builder to get rid of previous clauses
        $query->setQuery($newBase);
    }

    public function changeDriver($driver, $time = null, $withoutAlerts = false)
    {
        if (is_null($time))
            $time = date('Y-m-d H:i:s');

        $this->current_driver_id = $driver->id ?? null;
        $this->save();

        DB::table('user_driver_position_pivot')->insert([
            'device_id' => $this->id,
            'driver_id' => $driver->id ?? null,
            'date'      => $time
        ]);

        if (!$driver || $withoutAlerts)
            return;

        $position = $this->positionTraccar();

        if (is_null($position))
            return;

        $alerts = $this->alerts->filter(function($item){
            return $item->type == 'driver';
        });

        foreach ($alerts as $alert) {
            $event = $this->events()->create([
                'type'         => 'driver',
                'user_id'      => $alert->user_id,
                'alert_id'     => $alert->id,
                'device_id'    => $this->id,
                'geofence_id'  => null,
                'position_id'  => $position->id,
                'altitude'     => $position->altitude,
                'course'       => $position->course,
                'latitude'     => $position->latitude,
                'longitude'    => $position->longitude,
                'speed'        => $position->speed,
                'time'         => $position->time,
                'message'      => $driver->name,
                'additional'   => [
                    'driver_id'   => $driver->id,
                    'driver_name' => $driver->name
                ]
            ]);

            SendQueue::create([
                'user_id'   => $event->user_id,
                'type'      => $event->type,
                'data'      => $event,
                'channels'  => $alert->channels
            ]);
        }
    }

    public function setExpirationDateAttribute($value)
    {
        $this->attributes['expiration_date'] = is_null($value) ? '0000-00-00 00:00:00' : $value;
    }

    public function getExpirationDateAttribute($value)
    {
        if ($value == '0000-00-00') {
            return null;
        }

        if ($value == '0000-00-00 00:00:00') {
            return null;
        }

        return $value;
    }

    public function getWidgetCameras()
    {
        return $this->deviceCameras()->where('show_widget', 1)->get();
    }

    public function scopeHasExpiration($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('expiration_date');
            $q->where('expiration_date', '!=', '0000-00-00');
            $q->where('expiration_date', '!=', '0000-00-00 00:00:00');
        });
    }

    public function scopeHasntExpiration($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expiration_date');
            $q->orWhere('expiration_date', '=', '0000-00-00');
            $q->orWhere('expiration_date', '=', '0000-00-00 00:00:00');
        });
    }

    public function scopeIsExpiringAfter($query, $days)
    {
        return $query
            ->hasExpiration()
            ->where('expiration_date', '>=', Carbon::now())
            ->where('expiration_date', '<=', Carbon::now()->addDays($days));
    }

    public function scopeIsExpiredBefore($query, $days)
    {
        return $query
            ->hasExpiration()
            ->where('expiration_date', '<=', Carbon::now()->subDays($days));
    }

    public function scopeExpired($query) {
        return $query
            ->hasExpiration()
            ->where('expiration_date', '<=', Carbon::now());
    }

    public function scopeExpiredForLastDays($query, $days = 0)
    {
        return $query->expired()
            ->where('expiration_date', '>=', Carbon::today()->subDays($days));
    }

    public function scopeUnexpired($query)
    {
        return $query
            ->where(function($q) {
                $q->hasntExpiration();
                $q->orWhere(function($q2) {
                    $q2->hasExpiration()->where('expiration_date', '>', Carbon::now());
                });
            });
    }

    public function scopeFilterUserAbility($query, User $user, $ability = 'own') {
        return $query->with('users')->get()->filter(function($device) use ($user, $ability) {
            return $user->can($ability, $device);
        });
    }

    public function scopeWhereIdOrImei(Builder $query, $value)
    {
        return $query->where(function (Builder $query) use ($value) {
            $query->where('id', $value);
            $query->orWhere('imei', $value);
        });
    }

    public static function getFields()
    {
        $fields = [
            'name' => trans('validation.attributes.name'),
            'imei' => trans('validation.attributes.imei'),
            'sim_number' => trans('validation.attributes.sim_number'),
            'vin' => trans('validation.attributes.vin'),
            'device_model' => trans('validation.attributes.device_model'),
            'plate_number' => trans('validation.attributes.plate_number'),
            'registration_number' => trans('validation.attributes.registration_number'),
            'object_owner' => trans('validation.attributes.object_owner'),
            'tabela_fipe' => trans('validation.attributes.tabela_fipe'),
            'additional_notes' => trans('validation.attributes.additional_notes'),

            'fuel_quantity' => trans('validation.attributes.fuel_quantity'),
            'fuel_price' => trans('validation.attributes.fuel_price'),

            'users_emails' => trans('admin.users'),
            'protocol' => trans('front.protocol'),
            'latitude' => trans('front.latitude'),
            'longitude' => trans('front.longitude'),
            'altitude' => trans('front.altitude'),
            'course' => trans('front.course'),
            'speed' => trans('front.speed'),
            'last_connect_time' => trans('admin.last_connection'),
            'stop_duration' => trans('front.stop_duration'),

            'expiration_date' => trans('validation.attributes.expiration_date'),
        ];

        if (settings('plugins.additional_installation_fields.status')) {
            $fields['sim_activation_date'] = trans('validation.attributes.sim_activation_date');
            $fields['sim_expiration_date'] = trans('validation.attributes.sim_expiration_date');
            $fields['installation_date']   = trans('validation.attributes.installation_date');
        }

        return $fields;
    }

    public function getUsersEmailsAttribute()
    {
        return $this
            ->users
            ->filter(function($user){
                return auth()->user()->can('show', $user);
            })
            ->implode('email', ', ');
    }



    public function getImageAttribute()
    {
        $path = str_finish(self::IMAGE_PATH, '/') . "{$this->id}.*";

        return File::glob($path)[0] ?? null;
    }

    public function getPlanAttribute()
    {
        return $this->plans->first() ?? null;
    }

    public function getPlanIdAttribute()
    {

        return $this->plan->id ?? null;
    }

    public function getNameWithSimNumberAttribute()
    {
        return $this->name." ({$this->sim_number})";
    }

    public function isMove() {
        return $this->getStatus() == self::STATUS_ONLINE;
    }

    public function isIdle() {
        return $this->getStatus() == self::STATUS_ENGINE;
    }

    public function isStop() {
        return $this->getStatus() == self::STATUS_ACK;
    }

    public function isOffline() {
        return $this->getTimeoutStatus() === self::STATUS_OFFLINE;
    }

    public function isPark()
    {
        return $this->isStop() && ! $this->isIdle();
    }

    public function isOfflineFrom($date) {
        $time = strtotime( max($this->getServerTime(), $this->getAckTime()) );

        return Carbon::parse($date)->timestamp > $time;
    }

    public function isInactive()
    {
        $time = strtotime( max($this->getServerTime(), $this->getAckTime()) );

        return Carbon::now()->subMinutes(settings('main_settings.default_object_inactive_timeout'))->timestamp > $time;
    }

    public function isNeverConnected() {
        return is_null($this->getServerTime()) && is_null($this->getAckTime());
    }

    public function wasConnected() {
        return ! $this->isNeverConnected();
    }

    public function scopeTraccarJoin($query) {
        $traccar_db = config('database.connections.traccar_mysql.database');

        if ($query->isJoined("$traccar_db.devices as traccar"))
            return $query;

        //prevent traccar.devices id overwrite
        $selects = $query->getQuery()->columns;
        if (!($selects && in_array('devices.*', $selects))) {
            $query->select('devices.*');
        }

        return $query->leftJoin("$traccar_db.devices as traccar", 'devices.traccar_device_id', '=', 'traccar.id');
    }

    public function scopeWasConnected($query) {
        return $query
            ->traccarJoin()
            ->where(function($q) {
                $q->whereNotNull('traccar.server_time');
                $q->orWhereNotNull('traccar.ack_time');
            });
    }

    public function scopeNeverConnected($query) {
        return $query
            ->traccarJoin()
            ->whereNull('traccar.server_time')
            ->whereNull('traccar.ack_time');
    }

    public function scopeConnectedAfter($query, $time) {
        return $query
            ->traccarJoin()
            ->where(function($q) use ($time){
                $q->where('traccar.server_time', '>=', $time);
                $q->orWhere('traccar.ack_time', '>=', $time);
            });
    }

    public function scopeConnectedBefore($query, $time) {
        return $query
            ->traccarJoin()
            ->where(function($q) use ($time){
                $q->where('traccar.server_time', '<', $time);
                $q->where('traccar.ack_time', '<', $time);
            });
    }

    public function scopeOnline($query, $minutes = null) {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout');

        $time = Carbon::now()->subMinutes($minutes);

        return $query->connectedAfter($time);
    }

    public function scopeOffline($query, $minutes = null) {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout');

        $time = Carbon::now()->subMinutes($minutes);

        return $query
            ->traccarJoin()
            //ack_time or server_time can be NULL, this complicates datetime comparison
            ->whereRaw("GREATEST(COALESCE(traccar.ack_time, traccar.server_time), COALESCE(traccar.server_time, traccar.ack_time)) < '$time'");
    }

    public function scopeInactive($query, $minutes = null) {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_inactive_timeout');

        return $query->offline($minutes);
    }

    public function scopeMove($query) {

        return $query
            ->traccarJoin()
            ->online()
            ->whereNotNull('traccar.moved_at')
            ->whereRaw('traccar.moved_at > COALESCE(traccar.stoped_at, 0)');
    }

    public function scopeStop($query) {
        return $query
            ->traccarJoin()
            ->online()
            ->where(function($q) {
                $q->whereNull('traccar.moved_at');
                $q->orWhereRaw('COALESCE(traccar.stoped_at, 0) > traccar.moved_at');
            });
    }

    public function scopePark($query) {
        return $query
            ->engineOff()
            ->stop()
            ->online();
    }

    public function scopeIdle($query) {
        return $query
            ->engineOn()
            ->stop()
            ->online();
    }

    public function scopeEngineOn($query) {
        return $query
            ->traccarJoin()
            ->online()
            ->whereNotNull('traccar.engine_on_at')
            ->whereRaw('traccar.engine_on_at > COALESCE(traccar.engine_off_at, 0)');
    }

    public function scopeEngineOff($query) {
        return $query
            ->traccarJoin()
            ->online()
            ->whereNotNull('traccar.engine_off_at')
            ->whereRaw('traccar.engine_off_at > COALESCE(traccar.engine_on_at, 0)');
    }

    public function scopeProtocol($query, $protocol)
    {
        $query->traccarJoin();

        if (is_null($protocol))
            $query->whereNull('traccar.protocol');

        if (is_array($protocol))
            $query->whereIn('traccar.protocol', $protocol);

        if (is_string($protocol))
            $query->where('traccar.protocol', $protocol);

        return $query;
    }

    public function activate(DevicePlan $plan, $expirationDate)
    {
        $this->plans()
            ->sync([$plan->id]);
        $this->update([
            'expiration_date' => $expirationDate,
        ]);

        event(new DeviceSubscriptionActivate($this));
    }

    public function renew($expirationDate)
    {
        $this->setExpirationDate($expirationDate);

        event(new DeviceSubscriptionRenew($this));
    }

    public function setExpirationDate($expirationDate)
    {
        $this->update([
            'expiration_date' => $expirationDate,
        ]);
    }

    public function remove()
    {
        $this->users()->sync([]);
        $this->events()->delete();
        $this->sensors()->delete();
        $this->services()->delete();
        DB::table('user_drivers')->where('device_id', $this->id)->update(['device_id' => null]);

        if ($this->traccar) {
            $connection = $this->positions()->getRelated()->getConnectionName();
            $tableName = $this->positions()->getRelated()->getTable();

            if (Schema::connection($connection)->hasTable($tableName)) {
                DB::connection($connection)->table($tableName)->truncate();
                Schema::connection($connection)->dropIfExists($tableName);
            }

            $this->traccar->delete();
        }

        $this->delete();
    }
}
