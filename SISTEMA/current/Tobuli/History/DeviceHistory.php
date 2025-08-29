<?php

namespace Tobuli\History;


use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Device;
use Tobuli\History\Actions\SensorsValues;


class DeviceHistory
{
    protected $config;

    protected $user;

    protected $device;

    protected $actions;

    protected $previousPosition;

    protected $date_from;
    protected $date_to;

    protected $list;

    protected $groups;

    protected $root;

    protected $geofences;

    public $sensors;
    public $sensors_data;

    public function __construct(Device $device)
    {
        $this->device = $device;

        $this->config = [
            'chunk_size'   => 5000,
            'stop_speed'   => 6,
            'stop_seconds' => 120,
            'speed_limit'  => null,

            'min_fuel_fillings' => 10,
            'min_fuel_thefts'   => 10,
        ];

        $this->root = new Group('root');
        $this->groups = new GroupContainer();
    }

    public function config($key)
    {
        return $this->config[$key];
    }

    public function allConfig(): array
    {
        return $this->config;
    }

    public function hasConfig($key)
    {
        return array_key_exists($key, $this->config);
    }

    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function setRange($from, $to)
    {
        $this->date_from = $from;
        $this->date_to = $to;
    }

    public function setSensors($sensors)
    {
        $this->sensors = $sensors;
    }

    public function getDateFrom()
    {
        return $this->date_from;
    }

    public function getDateTo()
    {
        return $this->date_to;
    }

    /**
     * @return Group
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return Group
     */
    public function & root()
    {
        return $this->root;
    }

    /**
     * @return GroupContainer
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return GroupContainer
     */
    public function & groups()
    {
        return $this->groups;
    }

    public function getSensorsData()
    {
        return $this->sensors_data;
    }

    public function registerActions($actionClasses)
    {

        if ( ! is_array($actionClasses))
            $actionClasses = [$actionClasses];

        $i = 0;

        $counts = [];

        while (($class = array_shift($actionClasses)) && $i++ < 100)
        {
            if ( ! class_exists($class))
                $class = "Tobuli\History\Actions\\" . studly_case($class);

            if ( ! class_exists($class)) {
                throw new \Exception("Not found DeviceHistory action class '$class'");
            }

            if (empty($counts[$class]))
                $counts[$class] = 0;

            $counts[$class]++;

            $requires = $class::required();

            if ( ! $requires)
                continue;

            $callerCount = $counts[$class];

            foreach ($requires as $require)
            {
                if (empty($counts[$require]))
                    $counts[$require] = 0;

                $counts[$require] += $callerCount;

                array_push($actionClasses, $require);
            }
        }

        array_walk($counts, function(&$count, $class) { $count = $count + $class::RADIO; });
        arsort($counts);

        foreach ($counts as $class => $count)
            $this->actions[] = $class;

        if ($this->actions)
            $this->actions = array_unique($this->actions);

        return $this;
    }

    protected function bootActions()
    {
        if ($this->sensors)
            $this->registerActions([SensorsValues::class]);

        $actions = $this->actions;

        $this->actions = [];

        foreach ($actions as $action)
            $this->actions[] = new $action($this);

        foreach ($this->actions as $action)
        {
            $action->boot();
        }
    }

    public function get()
    {
        $this->doit();

        return [
            'root'   => $this->root,
            'groups' => $this->groups,
        ];
    }

    protected function doit()
    {
        $this->bootActions();
        $this->queryPositions($this->date_from, $this->date_to);
    }

    protected function queryPositions($from, $to)
    {
        $columns = [
            'id',
            'altitude',
            'course',
            'latitude',
            'longitude',
            'other',
            'speed',
            'time',
            'device_time',
            'server_time',
            'valid',
            'sensors_values'
        ];

        $connection = $this->device->positions()->getRelated()->getConnectionName();
        $tableName = $this->device->positions()->getRelated()->getTable();

        DB::disableQueryLog();
        DB::connection($connection)->disableQueryLog();

        $all = DB::connection($connection)
            ->table($tableName)
            ->select($columns)
            ->whereBetween('time', [$from, $to])
            ->orderBy('time')
            ->orderBy('id');

        $first = null;
        $last_position = null;

        $all->chunk($this->config('chunk_size'), function($positions) use (& $first, & $last_position){

            $this->preproccess($positions);

            foreach($positions as $position)
            {
                $this->proccess($position);

                if (is_null($first) && $first = true)
                    $this->root->setStartPosition($position);
            }

            if( ! empty($position))
                $last_position = $position;
        });

        if( ! empty($last_position)) {
            $this->root->setEndPosition($last_position);

            foreach ($this->groups->actives() as $group) {
                if (!$group->isLastClose())
                    continue;

                $this->groupEnd($group->getKey(), $last_position);
            }
        }

    }

    protected function preproccess($positions)
    {
        foreach ($this->actions as $action)
        {
            $action->preproccess($positions);
        }
    }

    protected function proccess(&$position)
    {
        $this->proceed = false;

        foreach ($this->actions as & $action)
        {
            $action->doIt($position);

            if ( ! empty($position->break))
                break;

            if ($this->proceed)
                break;
        }

        $this->groups()->disactiveClosed();

        //unset($this->previousPosition);
        $this->previousPosition = $position;
    }

    public function getDevice()
    {
        return $this->device;
    }

    public function getSensor($type)
    {
        return $this->device->getSensorByType($type);
    }

    public function getPrevPosition()
    {
        return $this->previousPosition;
    }


    public function hasStat($key): bool
    {
        return $this->root->stats()->has($key);
    }

    public function setStat($key, $stat)
    {
        $this->root->stats()->set($key, $stat);
    }

    public function registerStat($key, $stat)
    {
        $this->root->stats()->set($key, $stat);
    }

    public function applyStat($key, $value)
    {
        if ($key != 'positions')
            $this->root->applyStat($key, $value);

        $this->groups->applyStat($key, $value);
    }


    public function setProceed()
    {
        $this->proceed = true;
    }

    public function addList($position)
    {
        if (empty($this->list))
            $this->listPreviousPosition = $this->previousPosition;

        $this->list[] = $position;
    }

    public function getList()
    {
        return $this->list;
    }


    public function getListFirst()
    {
        $item = reset($this->list);

        return is_array($item) ? $item[0] : $item;
    }

    public function processList($closure)
    {
        foreach ($this->list as & $item) {

            $position = is_array($item) ? $item[0] : $item;

            $item = call_user_func($closure, $position);
        }
    }

    public function doitList()
    {
        $this->previousPosition = $this->listPreviousPosition;

        $i = 0;
        $count = count($this->list);

        while($i++ < $count && $position = array_shift($this->list)) {
            $this->proccess($position);
        }
    }


    public function groupStart($key, $position)
    {
        if ($key instanceof Group) {
            $group = $key;
        } else {
            $group = new Group($key);
        }

        $group->setStartPosition($position);
        $group->stats()->_clone( $this->root->stats()->all() );

        return $this->groups->open($group);
    }

    public function groupEnd($key, $position, $properties = [])
    {
        $this->groups->close($key, $position, $properties);
    }

    public function applyRoute($position)
    {
        if ($this->groups->hasActives()) {
            $lastGroup = $this->groups->last();
            $lastGroup->route()->apply($position);
        } else {
            $this->root->route()->apply($position);
        }

        unset($lastGroup);
    }

    public function setGeofences( & $geofences)
    {
        $this->geofences = $geofences;
    }

    public function getGeofences()
    {
        return $this->geofences;
    }

    public function inGeofences($position)
    {
        $inGeofences = [];

        if (empty($this->geofences))
            return $inGeofences;

        foreach ($this->geofences as $geofence)
        {
            if ( ! $geofence->pointIn($position))
                continue;

            $inGeofences[] = $geofence->id;
        }

        return $inGeofences;
    }

    public function debug()
    {
        $start = microtime(true);
        $this->doit();
        $time = microtime(true) - $start;

        echo "<br>";
        echo "Device: {$this->device->imei}<br>";

        $stats = $this->root->stats();

        foreach ($stats->all() as $key => $value)
            {
            if (is_object($value)) {
                echo "$key: " . $value->human() . "<br>";
            } else {
                echo "$key: {$value}<br>";
            }
        }

        echo "Groups: " . count($this->groups->all()) . "<br>";
        echo "Memory: " . (memory_get_usage(true) / 1024 / 1024) . "MB<br>";
        //echo "Object: " . ($this->getMemoryUsage($this) / 1024 / 1024) . "MB<br>";
        echo "Proccess: " . round($time, 4) . "s<br>";

        //dd($this->groups);
    }

    public function __destruct()
    {
        $this->actions = null;
        $this->sensors = null;
        $this->root = null;
        $this->groups = null;
        $this->sensors_data = null;
    }

}