<?php namespace ModalHelpers;

use App\Exceptions\DeviceLimitException;
use App\Exceptions\PermissionException;
use App\Transformers\Device\DeviceMapTransformer;
use Carbon\Carbon;
use CustomFacades\ModalHelpers\SensorModalHelper;
use CustomFacades\ModalHelpers\ServiceModalHelper;
use CustomFacades\Repositories\DeviceGroupRepo;
use CustomFacades\Repositories\DeviceIconRepo;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\DeviceSensorRepo;
use CustomFacades\Repositories\EventRepo;
use CustomFacades\Repositories\SensorGroupRepo;
use CustomFacades\Repositories\SensorGroupSensorRepo;
use CustomFacades\Repositories\TimezoneRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Repositories\DeviceCameraRepo;
use CustomFacades\Validators\DeviceConfiguratorFormValidator;
use CustomFacades\Validators\DeviceFormValidator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\ApnConfig;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceConfig;
use Tobuli\Entities\DeviceType;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\SMS\SMSGatewayManager;
use Tobuli\Services\CustomValuesService;
use Tobuli\Services\DeviceConfigService;
use Formatter;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;
use Tobuli\Services\FractalTransformerService;

class DeviceModalHelper extends ModalHelper
{
    private $device_fuel_measurements = [];
    private $configService;
    private $customValueService;

    /**
     * @var FractalTransformerService
     */
    private $transformerService;

    public function __construct(DeviceConfigService $configService, CustomValuesService $customValueService, FractalTransformerService $transformerService)
    {
        parent::__construct();

        $this->device_fuel_measurements = [
            [
                'id' => 1,
                'title' => trans('front.l_km'),
                'fuel_title' => strtolower(trans('front.liter')),
                'distance_title' => trans('front.kilometers'),
            ],
            [
                'id' => 2,
                'title' => trans('front.mpg'),
                'fuel_title' => strtolower(trans('front.gallon')),
                'distance_title' => trans('front.miles'),
            ],
            [
                'id' => 3,
                'title' => trans('front.kwh_km'),
                'fuel_title' => strtolower(trans('front.kwh')),
                'distance_title' => trans('front.kilometers'),
            ]
        ];
        $this->configService = $configService;
        $this->customValueService = $customValueService;

        $this->transformerService = $transformerService->setSerializer(WithoutDataArraySerializer::class);
    }

    public function createData() {
        $perm = request()->get('perm');

        if ($perm == null || ($perm != null && $perm != 1)) {
            if ($perm != null && $perm != 2) {
                if ($this->reachedDeviceLimit()) {
                    throw new DeviceLimitException();
                }
            }

            $this->checkException('devices', 'create');
        }

        $icons_type = [
            'arrow' => trans('front.arrow'),
            'rotating' => trans('front.rotating_icon'),
            'icon' => trans('front.icon')
        ];

        $device_icon_colors = [
            'green'  => trans('front.green'),
            'yellow' => trans('front.yellow'),
            'red'    => trans('front.red'),
            'blue'   => trans('front.blue'),
            'orange' => trans('front.orange'),
            'black'  => trans('front.black'),
        ];
        $device_icons = DeviceIconRepo::getMyIcons($this->user->id);
        $device_icons_grouped = [];

        foreach ($device_icons as $dicon) {
            if ($dicon['type'] == 'arrow') {
                continue;
            }

            if (!array_key_exists($dicon['type'], $device_icons_grouped)) {
                $device_icons_grouped[$dicon['type']] = [];
            }

            $device_icons_grouped[$dicon['type']][] = $dicon;
        }

        $users = UserRepo::getUsers($this->user);

        $device_groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $expiration_date_select = [
            '0000-00-00 00:00:00' => trans('front.unlimited'),
            '1' => trans('validation.attributes.expiration_date')
        ];

        $timezones = TimezoneRepo::order()
            ->pluck('title', 'id')
            ->prepend(trans('front.default'), '0')
            ->all();

        $timezones_arr = [];

        foreach ($timezones as $key => &$timezone) {
            $timezone = str_replace('UTC ', '', $timezone);

            if ($this->api) {
                array_push($timezones_arr, ['id' => $key, 'value' => $timezone]);
            }
        }

        $sensor_groups = [];

        if (isAdmin()) {
            $sensor_groups = SensorGroupRepo::getWhere([], 'title')
                    ->pluck('title', 'id')
                    ->prepend(trans('front.none'), '0')
                    ->all();
        }

        $device_fuel_measurements = $this->device_fuel_measurements;

        $device_fuel_measurements_select =  [];

        foreach ($device_fuel_measurements as $dfm) {
            $device_fuel_measurements_select[$dfm['id']] = $dfm['title'];
        }

        if ($this->api) {
            $timezones = $timezones_arr;
            $device_groups = apiArray($device_groups);
            $sensor_groups = apiArray($sensor_groups);
            $users = $users->toArray();
        }

        $device_configs = [];
        $apn_configs = [];

        if ($this->user->able('configure_device')) {
            $device_configs = DeviceConfig::active()
                ->get()
                ->pluck('fullName', 'id');
            $apn_configs = ApnConfig::active()
                ->get()
                ->pluck('name', 'id');
        }

        $device_types = DeviceType::active()->get()->pluck('title', 'id');

        return compact('device_groups', 'sensor_groups',
            'device_fuel_measurements', 'device_icons', 'users', 'timezones',
            'expiration_date_select', 'device_fuel_measurements_select',
            'icons_type', 'device_icons_grouped', 'device_icon_colors',
            'device_configs', 'apn_configs', 'device_types'
        );
    }

    public function create()
    {
        $this->checkException('devices', 'store');

        if ($this->reachedDeviceLimit()) {
            throw new DeviceLimitException();
        }

        $this->data['imei'] = isset($this->data['imei']) ? trim($this->data['imei']) : null;
        $this->data['group_id'] = !empty($this->data['group_id']) ? $this->data['group_id'] : null;
        $this->data['timezone_id'] = empty($this->data['timezone_id']) ? NULL : $this->data['timezone_id'];
        $this->data['snap_to_road'] = isset($this->data['snap_to_road']);
        $this->data['fuel_quantity'] = empty($this->data['fuel_quantity']) ? 0 : $this->data['fuel_quantity'];

        if ( ! empty($this->data['sim_activation_date']) && settings('plugins.annual_sim_expiration.status')) {
            $this->data['sim_expiration_date'] = Carbon::createFromTimestamp(strtotime($this->data['sim_activation_date']))
                ->addDays(settings('plugins.annual_sim_expiration.options.days'))
                ->toDateString();
        }

        if (array_key_exists('enable_expiration_date', $this->data) && empty($this->data['enable_expiration_date'])) {
            $this->data['expiration_date'] = '0000-00-00 00:00:00';
        }

        $this->data = onlyEditables(new Device(), $this->user, $this->data);

        if (! empty($this->data['expiration_date']) && $this->data['expiration_date'] != '0000-00-00 00:00:00') {
            $this->data['expiration_date'] = Formatter::time()->reverse($this->data['expiration_date']);
        }

        if (settings('plugins.create_only_expired_objects.status') && ! $this->user->perm('device.expiration_date', 'edit')) {
            $expirationOffset = settings('plugins.create_only_expired_objects.options.offset') ?? 0;
            $expirationOffsetType = settings('plugins.create_only_expired_objects.options.offset_type') ?? 'days';
            $expirationDate = date('Y-m-d H:i:s', strtotime(" + {$expirationOffset} {$expirationOffsetType}"));

            $this->data['expiration_date'] = $expirationDate;
            $this->data['installation_date'] = date('Y-m-d');
        }

        if (array_key_exists('device_icons_type', $this->data) && $this->data['device_icons_type'] == 'arrow') {
            $this->data['icon_id'] = 0;
        }

        $users = $this->usersReachedLimit();

        if ( $users && ! $users->isEmpty()) {
            throw new ValidationException(['user_id' => trans('validation.attributes.devices_limit') . ': ' . $users->implode('email', ', ')]);
        }

        DeviceFormValidator::validate('create', $this->data);

        $this->data['fuel_per_km'] = convertFuelConsumption($this->data['fuel_measurement_id'], $this->data['fuel_quantity']);

        $item_ex = DeviceRepo::whereImei($this->data['imei']);

        if (!empty($item_ex)) {
            throw new ValidationException(['imei' => str_replace(':attribute', trans('validation.attributes.imei_device'), trans('validation.unique'))]);
        }

        $this->setAbleUsers();

        beginTransaction();

        try {
            if (empty($this->data['user_id'])) {
                $this->data['user_id'] = ['0' => $this->user->id];
            }

            if (empty($item_ex)) {
                if (empty($this->data['fuel_quantity'])) {
                    $this->data['fuel_quantity'] = 0;
                }

                if (empty($this->data['fuel_price'])) {
                    $this->data['fuel_price'] = 0;
                }

                $this->data['gprs_templates_only'] = (array_key_exists('gprs_templates_only', $this->data) && $this->data['gprs_templates_only'] == 1 ? 1 : 0);

                $device_icon_colors = [
                    'green'  => trans('front.green'),
                    'yellow' => trans('front.yellow'),
                    'red'    => trans('front.red'),
                    'blue'   => trans('front.blue'),
                    'orange' => trans('front.orange'),
                    'black'  => trans('front.black'),
                ];

                $this->data['icon_colors'] = [
                    'moving' => 'green',
                    'stopped' => 'red',
                    'offline' => 'red',
                    'engine' => 'yellow',
                ];

                if (array_key_exists('icon_moving', $this->data) && array_key_exists($this->data['icon_moving'], $device_icon_colors)) {
                    $this->data['icon_colors']['moving'] = $this->data['icon_moving'];
                }

                if (array_key_exists('icon_stopped', $this->data) && array_key_exists($this->data['icon_stopped'], $device_icon_colors)) {
                    $this->data['icon_colors']['stopped'] = $this->data['icon_stopped'];
                }

                if (array_key_exists('icon_offline', $this->data) && array_key_exists($this->data['icon_offline'], $device_icon_colors)) {
                    $this->data['icon_colors']['offline'] = $this->data['icon_offline'];
                }

                if (array_key_exists('icon_engine', $this->data) && array_key_exists($this->data['icon_engine'], $device_icon_colors)) {
                    $this->data['icon_colors']['engine'] = $this->data['icon_engine'];
                }

                $device = DeviceRepo::create($this->data);

                $this->deviceSyncUsers($device);
                $this->createSensors($device->id);

                $device->createPositionsTable();
            } else {
                DeviceRepo::update($item_ex->id, $this->data);
                $device = DeviceRepo::find($item_ex->id);
                $device->users()->sync($this->data['user_id']);
            }

            if ($this->user->can('edit', $device, 'custom_fields')) {
                $customValues = $this->data['custom_fields'] ?? null;
                $this->customValueService->saveCustomValues($device, $customValues);
            }

            commitTransaction();
        } catch (\Exception $e) {
            rollbackTransaction();

            throw $e;
        }

        if ($this->data['configure_device'] ?? false) {
            $this->configureDevice($device);
        }

        return ['status' => 1, 'id' => $device->id,];
    }

    public function editData() {
        $device_id = $this->data['id']
            ?? request()->route('id')
            ?? $this->data['device_id']
            ?? null;

        $item = DeviceRepo::find($device_id);

        $this->checkException('devices', 'edit', $item);

        $users = UserRepo::getUsers($this->user);

        $sel_users = $item->users->pluck('id', 'id')->all();
        $group_id = null;

        $timezone_id = $item->timezone_id;
        //$timezone_id = null;
        if ($item->users->contains($this->user->id)) {
            foreach ($item->users as $item_user) {
                if ($item_user->id == $this->user->id) {
                    $group_id = $item_user->pivot->group_id;
                    //$timezone_id = $item_user->pivot->timezone_id;
                    break;
                }
            }
        }

        $icons_type = [
            'arrow' => trans('front.arrow'),
            'rotating' => trans('front.rotating_icon'),
            'icon' => trans('front.icon')
        ];

        $device_icon_colors = [
            'green'  => trans('front.green'),
            'yellow' => trans('front.yellow'),
            'red'    => trans('front.red'),
            'blue'   => trans('front.blue'),
            'orange' => trans('front.orange'),
            'black'  => trans('front.black'),
        ];

        $device_icons = DeviceIconRepo::getMyIcons($this->user->id);
        
        $device_icons_grouped = [];

        foreach ($device_icons as $dicon) {
            if ($dicon['type'] == 'arrow') {
                continue;
            }

            if (!array_key_exists($dicon['type'], $device_icons_grouped)) {
                $device_icons_grouped[$dicon['type']] = [];
            }

            $device_icons_grouped[$dicon['type']][] = $dicon;
        }

        $device_groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $sensors = SensorModalHelper::paginated($item->id);
        $services = ServiceModalHelper::paginated($item->id);
        $expiration_date_select = [
            '0000-00-00 00:00:00' => trans('front.unlimited'),
            '1' => trans('validation.attributes.expiration_date')
        ];

        $has_sensors = DeviceSensorRepo::getWhereInWhere([
            'odometer',
            'acc',
            'engine',
            'ignition',
            'engine_hours'
        ], 'type', ['device_id' => $item->id]);

        $arr = parseSensorsSelect($has_sensors);
        $engine_hours = $arr['engine_hours'];
        $detect_engine = $arr['detect_engine'];
        unset($item->sensors);

        $timezones = TimezoneRepo::order()
            ->pluck('title', 'id')
            ->prepend(trans('front.default'), '0')
            ->all();

        foreach ($timezones as $key => &$timezone) {
            $timezone = str_replace('UTC ', '', $timezone);
        }

        $sensor_groups = [];

        if (isAdmin()) {
            $sensor_groups = SensorGroupRepo::getWhere([], 'title')
                    ->pluck('title', 'id')
                    ->prepend(trans('front.none'), '0')
                    ->all();
        }

        $device_fuel_measurements = $this->device_fuel_measurements;

        $device_fuel_measurements_select =  [];

        foreach ($device_fuel_measurements as $dfm) {
            $device_fuel_measurements_select[$dfm['id']] = $dfm['title'];
        }

        if ($this->api) {
            $device_groups = apiArray($device_groups);
            $timezones = apiArray($timezones);
            $users = $users->toArray();
        }

        $device_cameras = DeviceCameraRepo::searchAndPaginate(['filter' => ['device_id' => $device_id]], 'id', 'desc', 10);

        $device_types = DeviceType::active()->get()->pluck('title', 'id');

        return compact('device_id', 'engine_hours', 'detect_engine',
            'device_groups', 'sensor_groups', 'item',
            'device_fuel_measurements', 'device_icons', 'sensors', 'services',
            'expiration_date_select', 'timezones',
            'users', 'sel_users', 'group_id', 'timezone_id',
            'device_fuel_measurements_select', 'icons_type',
            'device_icons_grouped', 'device_icon_colors', 'device_cameras', 'device_types'
        );
    }

    public function edit()
    {
        $this->data['id'] = $this->data['id']
            ?? $this->data['device_id']
            ?? null;

        $item = DeviceRepo::find($this->data['id']);

        if ( ! empty($this->data['sim_activation_date']) && settings('plugins.annual_sim_expiration.status')) {
            $this->data['sim_expiration_date'] = Carbon::createFromTimestamp(strtotime($this->data['sim_activation_date']))
                ->addDays(settings('plugins.annual_sim_expiration.options.days'))
                ->toDateString();
        }

        if (array_key_exists('enable_expiration_date', $this->data) && empty($this->data['enable_expiration_date'])) {
            $this->data['expiration_date'] = '0000-00-00 00:00:00';
        }

        if (! empty($this->data['expiration_date']) && $this->data['expiration_date'] != '0000-00-00 00:00:00') {
            $this->data['expiration_date'] = Formatter::time()->reverse($this->data['expiration_date']);
        }

        $this->checkException('devices', 'update', $item);

        $this->data = onlyEditables($item, $this->user, $this->data);

        $this->data['group_id'] = !empty($this->data['group_id']) ? $this->data['group_id'] : null;
        $this->data['snap_to_road'] = isset($this->data['snap_to_road']);
        $this->data['fuel_quantity'] = empty($this->data['fuel_quantity']) ? 0 : $this->data['fuel_quantity'];

        $this->setAbleUsers($item);

        $prev_timezone_id = $item->timezone_id;

        if ( ! empty($this->data['timezone_id']) && $this->data['timezone_id'] != 57 && $item->isCorrectUTC()) {
            throw new ValidationException(['timezone_id' => 'Device time is correct. Check your timezone Setup -> Main -> Timezone']);
        }

        if (array_key_exists('device_icons_type', $this->data) && $this->data['device_icons_type'] == 'arrow') {
            $this->data['icon_id'] = 0;
        }

        $users = $this->usersReachedLimit($item);

        if ( $users && ! $users->isEmpty()) {
            throw new ValidationException(['user_id' => trans('validation.attributes.devices_limit') . ': ' . $users->implode('email', ', ')]);
        }

        DeviceFormValidator::validate('update', $this->data, $item->id);

        $this->data['fuel_per_km'] = convertFuelConsumption($this->data['fuel_measurement_id'], $this->data['fuel_quantity']);

        beginTransaction();

        try {
            $device_icon_colors = [
                'green'  => trans('front.green'),
                'yellow' => trans('front.yellow'),
                'red'    => trans('front.red'),
                'blue'   => trans('front.blue'),
                'orange' => trans('front.orange'),
                'black'  => trans('front.black'),
            ];

            $this->data['icon_colors'] = [
                'moving' => 'green',
                'stopped' => 'red',
                'offline' => 'red',
                'engine' => 'yellow',
            ];

            if (array_key_exists('icon_moving', $this->data) && array_key_exists($this->data['icon_moving'], $device_icon_colors)) {
                $this->data['icon_colors']['moving'] = $this->data['icon_moving'];
            }

            if (array_key_exists('icon_stopped', $this->data) && array_key_exists($this->data['icon_stopped'], $device_icon_colors)) {
                $this->data['icon_colors']['stopped'] = $this->data['icon_stopped'];
            }

            if (array_key_exists('icon_offline', $this->data) && array_key_exists($this->data['icon_offline'], $device_icon_colors)) {
                $this->data['icon_colors']['offline'] = $this->data['icon_offline'];
            }

            if (array_key_exists('icon_engine', $this->data) && array_key_exists($this->data['icon_engine'], $device_icon_colors)) {
                $this->data['icon_colors']['engine'] = $this->data['icon_engine'];
            }

            //DTRefactor
            //DeviceRepo::update($item->id, $this->data);
            $item->update($this->data);

            $this->deviceSyncUsers($item);
            $this->createSensors($item->id);

            if ($this->user->can('edit', $item, 'custom_fields')) {
                $customValues = $this->data['custom_fields'] ?? null;
                $this->customValueService->saveCustomValues($item, $customValues);
            }

            commitTransaction();
        } catch (\Exception $e) {
            rollbackTransaction();

            throw $e;
        }

        if ($prev_timezone_id != $item->timezone_id) {
            $item->applyPositionsTimezone();
        }

        return ['status' => 1, 'id' => $item->id];
    }

    public function resetAppUuid(int $id): array
    {
        /** @var Device $item */
        $item = Device::findOrFail($id);

        $this->checkException('devices', 'edit', $item);

        $item->app_uuid = null;
        $success = $item->save();

        return ['status' => (int)$success, 'id' => $item->id];
    }

    public function destroy()
    {
        $imei = $this->data['imei'] ?? null;

        if (!is_null($imei)) {
            $item = DeviceRepo::whereImei($imei);
        } else {
            $device_id = $this->data['id'] ?? $this->data['device_id'] ?? null;
            $item = DeviceRepo::find($device_id);
        }

        $this->checkException('devices', 'remove', $item);

        beginTransaction();

        try {
            $item->remove();
            commitTransaction();
        } catch (\Exception $e) {
            rollbackTransaction();

            throw $e;
        }

        return ['status' => 1, 'id' => $item->id, 'deleted' => 1];
    }

    public function detach()
    {
        $device_id = $this->data['id']
            ?? $this->data['device_id']
            ?? null;

        $item = DeviceRepo::find($device_id);

        $this->checkException('devices', 'own', $item);

        $item->users()->detach($this->user->id);

        return ['status' => 1];
    }

    public function changeActive()
    {
        $validator = Validator::make($this->data, [
            'id' => 'required_without:group_id',
            'group_id' => 'required_without:id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $active = (isset($this->data['active']) && filter_var($this->data['active'], FILTER_VALIDATE_BOOLEAN)) ? 1 : 0;

        $query = DB::table('user_device_pivot')
            ->where('user_id', $this->user->id);

        if (array_key_exists('group_id', $this->data)) {
            if ($group_id = $this->data['group_id']) {
                $group_id = is_array($group_id) ? $group_id : [$group_id];
                $query->whereIn('group_id',$group_id);
            } else {
                $query->whereNull('group_id');
            }
        } else {
            if ($id = $this->data['id']) {
                $id = is_array($id) ? $id : [$id];
                $query->whereIn('device_id',$id);
            }
        }

        $query->update([
            'active' => $active
        ]);

        return ['status' => 1];
    }

    public function itemsJson()
    {
        $this->checkException('devices', 'view');

        $time = time();

        if ( empty($this->data['time']) ) {
            $this->data['time'] = $time - 5;
        }

        $this->data['time'] = intval($this->data['time']);

        $items = $this->user->devices()
            ->with(['sensors', 'services', 'driver', 'traccar', 'icon'])
            ->filter($this->data)
            ->connectedAfter(date('Y-m-d H:i:s', $this->data['time']))
            ->clearOrdersBy()->get()->map(function($device) {
                if ($this->api)
                    return $this->generateJson($device, TRUE, TRUE);;

                return $this->transformerService->item($device, DeviceMapTransformer::class)->toArray();
            });
            /* chunks without pivot data, fix in 5.7 version
            ->chunkById(500, function($devices) use (&$items) {
                foreach ($devices as $item) {
                    $items[] = $this->generateJson($item, TRUE, TRUE);
                }
            }, 'devices.id', 'id');
            */

        $events = EventRepo::getHigherTime($this->user->id, $this->data['time'], $this->data['id'] ?? null);

        $events = $events->map(function($event) {
            $_event = $event->toArray();

            unset($_event['geofence'], $_event['device']);

            $_event['sound'] = array_get($event, 'alert.notifications.sound.active', false) ? asset('assets/audio/hint.mp3') : null;
            $_event['color'] = array_get($event, 'alert.notifications.color.input');
            $_event['delay'] = array_get($event, 'alert.notifications.auto_hide.active', true) ? 10 : 0;
            $_event['time'] = Formatter::time()->convert($_event['time']);
            $_event['speed'] = Formatter::speed()->format($_event['speed']);
            $_event['altitude'] = Formatter::altitude()->format($_event['altitude']);
            $_event['message'] = $event->title;
            $_event['device_name'] = $event->device->name ?? null;
            $_event['device'] = $event->device ? [
                'id'   => $event->device->id,
                'name' => htmlentities($event->device->name)
            ] : null;

            if ($event->geofence) {
                $_event['geofence'] = [
                    'id' => $event->geofence->id,
                    'name' => htmlentities($event->geofence->name)
                ];
            }

            return $_event;
        });

        return ['items' => $items, 'events' => $events, 'time' => $time, 'version' => Config::get('tobuli.version')];
    }

    public function generateJson($device, $json = TRUE, $device_info = FALSE) {
        $status = $device->getStatus();

        $json = $json && $this->api;

        $data = [];

        if ($this->api && $device_info) {
            $device_data = $device->toArray();

            if (isset($device_data['users'])) {
                $filtered_users = $device->users->filter(function ($user) {
                    return $this->user->can('show', $user);
                });

                $device_data['users'] = $this->formatUserList($filtered_users);
            }

            $device_data['lastValidLatitude']  = floatval($device->lat);
            $device_data['lastValidLongitude'] = floatval($device->lng);
            $device_data['latest_positions']   = $device->latest_positions;
            $device_data['icon_type'] = $device->icon->type;

            $device_data['active'] = intval($device->pivot->active);
            $device_data['group_id'] = intval($device->pivot->group_id);

            $device_data['user_timezone_id'] = null;
            $device_data['timezone_id'] = is_null($device->timezone_id) ? null : intval($device->timezone_id);

            $device_data['id'] = intval($device->id);
            $device_data['user_id'] = intval($device->pivot->user_id);
            $device_data['traccar_device_id'] = intval($device->traccar_device_id);
            $device_data['icon_id'] = intval($device->icon_id);
            $device_data['deleted'] = intval($device->deleted);
            $device_data['fuel_measurement_id'] = intval($device->fuel_measurement_id);
            $device_data['tail_length'] = intval($device->tail_length);
            $device_data['min_moving_speed'] = intval($device->min_moving_speed);
            $device_data['min_fuel_fillings'] = intval($device->min_fuel_fillings);
            $device_data['min_fuel_thefts'] = intval($device->min_fuel_thefts);
            $device_data['snap_to_road'] = intval($device->snap_to_road);
            $device_data['gprs_templates_only'] = intval($device->gprs_templates_only);
            $device_data['group_id'] = intval($device->pivot->group_id);
            $device_data['current_driver_id'] = is_null($device->current_driver_id) ? null : intval($device->current_driver_id);
            $device_data['pivot']['user_id'] = intval($device->pivot->user_id);
            $device_data['pivot']['device_id'] = intval($device->id);
            $device_data['pivot']['group_id'] = intval($device->pivot->group_id);
            $device_data['pivot']['current_driver_id'] = is_null($device->current_driver_id) ? null : intval($device->current_driver_id);
            //$device_data['pivot']['timezone_id'] = is_null($device->pivot->timezone_id) ? null : intval($device->pivot->timezone_id);
            $device_data['pivot']['timezone_id'] = null;
            $device_data['pivot']['active'] = intval($device->pivot->active);
            
            $device_data['time'] = $device->getTime();
            $device_data['course'] = isset($device->course) ? $device->course : null;
            $device_data['speed'] = $device->speed;

            $data = [
                'device_data' => $device_data
            ];
        }

        $driver = $device->driver;
        $inaccuracy = $device->getParameter('inaccuracy');

        return [
                'id'            => intval($device->id),
                'alarm'         => is_null($this->user->alarm) ? 0 : $this->user->alarm,
                'name'          => $device->name,
                'online'        => $status,
                'time'          => $device->time,
                'timestamp'     => $device->timestamp,
                'acktimestamp'  => $device->ack_timestamp,
                'lat'           => floatval($device->lat),
                'lng'           => floatval($device->lng),
                'course'        => (isset($device->course) ? $device->course : '-'),
                'speed'         => $device->speed,
                'altitude'      => $device->altitude,
                'icon_type'     => $device->icon->type,
                'icon_color'    => $device->getStatusColor(),
                'icon_colors'   => $device->icon_colors,
                'icon'          => $device->icon->toArray(),
                'power'         => '-',
                'address'       => '-',
                'protocol'      => $device->getProtocol($this->user) ? $device->getProtocol($this->user) : '-',
                'driver'        => ($driver ? $driver->name : '-'),
                'driver_data'   => $driver ? $driver : [
                    'id' => NULL,
                    'user_id' => NULL,
                    'device_id' => NULL,
                    'name' => NULL,
                    'rfid' => NULL,
                    'phone' => NULL,
                    'email' => NULL,
                    'description' => NULL,
                    'created_at' => NULL,
                    'updated_at' => NULL,
                ],
                'sensors'            => $json ? json_encode($device->getFormatSensors()) : $device->getFormatSensors(),
                'services'           => $json ? json_encode($device->getFormatServices()) : $device->getFormatServices(),
                'tail'               => $json ? json_encode($device->tail) : $device->tail,
                'distance_unit_hour' => $this->user->unit_of_speed,
                'unit_of_distance'   => $this->user->unit_of_distance,
                'unit_of_altitude'   => $this->user->unit_of_altitude,
                'unit_of_capacity'   => $this->user->unit_of_capacity,
                'stop_duration'      => $device->stop_duration,
                'stop_duration_sec'  => $device->getStopDuration() ?? 0,
                'moved_timestamp'    => $device->moved_timestamp,
                'engine_status'      => $device->getEngineStatus(),
                'detect_engine'      => $device->detect_engine,
                'engine_hours'       => $device->engine_hours,
                'total_distance'     => $device->getTotalDistance(),
                'inaccuracy'         => is_null($inaccuracy) ? null : intval($inaccuracy),
            ] + $data;
    }

    public function reachedDeviceLimit($user = NULL) {
        if (is_null($user)) {
            $user = $this->user;
        }

        if ($this->reachedServerDeviceLimit()) {
            return true;
        }

        if ($this->reachedUserDeviceLimit($user)) {
            return true;
        }

        return false;
    }

    private function reachedUserDeviceLimit($user, $exceed_only = false)
    {
        if (is_null($user->devices_limit)) {
            return false;
        }

        $count = $user->isManager() ? getManagerUsedLimit($user->id) : $user->devices()->count();

        if ($exceed_only) {
            return $count > $user->devices_limit;
        }

        return $count >= $user->devices_limit;
    }

    private function reachedServerDeviceLimit()
    {
        $limit = config('server.device_limit');

        if ( ! $limit) {
            return false;
        }

        if (1 == $limit) {
            return false;
        }

        $count = DeviceRepo::count();

        return $count >= $limit;
    }

    # Sensor groups
    private function createSensors($device_id) {
        if ( ! isAdmin()) {
            return;
        }

        if ( ! isset($this->data['sensor_group_id'])) {
            return;
        }

        $group_sensors = SensorGroupSensorRepo::getWhere(['group_id' => $this->data['sensor_group_id']]);

        if (empty($group_sensors)) {
            return;
        }

        foreach ($group_sensors as $sensor) {
            $sensor = $sensor->toArray();

            if ( ! $sensor['show_in_popup']) {
                unset($sensor['show_in_popup']);
            }

            if (in_array($sensor['type'], ['harsh_acceleration', 'harsh_breaking', 'harsh_turning'])) {
                $sensor['parameter_value'] = $sensor['on_value'];
            }

            SensorModalHelper::setData(array_merge([
                'user_id' => $this->user->id,
                'device_id' => $device_id,
                'sensor_type' => $sensor['type'],
                'sensor_name' => $sensor['name'],
            ], $sensor));

            SensorModalHelper::create();
        }
    }

    public function deviceSyncUsers($device) {
        if (isset($this->data['user_id'])) {
            if ( ! $this->user->isGod()) {
                $admin_user = DB::table('users')
                    ->select('users.id')
                    ->join('user_device_pivot', 'users.id', '=', 'user_device_pivot.user_id')
                    ->where(['users.email' => 'admin@admin.com'])
                    ->where(['user_device_pivot.device_id' => $device->id])
                    ->first();

                if ($admin_user) {
                    $this->data['user_id'][$admin_user->id] = $admin_user->id;
                }
            }

            $device->users()->sync($this->data['user_id']);
        }

        DB::table('user_device_pivot')
            ->where([
                'device_id' => $device->id,
                'user_id' => $this->user->id
            ])
            ->update([
                'group_id' => $this->data['group_id'],
                //'timezone_id' => $this->data['timezone_id'] == 0 ? NULL : $this->data['timezone_id']
            ]);
    }

    private function formatUserList($users)
    {
        return $users
            ->map(function($user) {
                return [
                    'id' => intval($user['id']),
                    'email' => $user['email']
                ];
            })
            ->all();
    }

    private function usersReachedLimit($device = null)
    {
        if (empty($this->data['user_id'])) {
            return null;
        }

        $userIds = is_array($this->data['user_id']) ? $this->data['user_id'] : [$this->data['user_id']];

        $users = User::whereIn('id', $userIds)
            ->whereNotNull('devices_limit')
            ->with(['devices' => function($q) use ($device){
                $q->where('user_device_pivot.device_id', $device ? $device->id : null);
            }])
            ->get();

        return $users->filter(function($user) {
            $hasThisDevice = ! $user->devices->isEmpty();

            return $this->reachedUserDeviceLimit($user, $hasThisDevice);
        });
    }

    private function configureDevice(Device $device)
    {
        if (! $this->user->able('configure_device')) {
            throw new PermissionException(['id' => trans('front.dont_have_permission')]);
        }

        DeviceConfiguratorFormValidator::validate('configure', $this->data);

        $config = DeviceConfig::find($this->data['config_id']);

        $smsManager = new SMSGatewayManager();
        $gatewayArgs = settings('sms_gateway.use_as_system_gateway')
            ? ['request_method' => 'system']
            : null;

        $smsSenderService = $smsManager->loadSender($this->user, $gatewayArgs);
        $apnData = request()->all(['apn_name', 'apn_username', 'apn_password']);

        if ($this
            ->configService
            ->setSmsManager($smsSenderService)
            ->configureDevice($device->sim_number, $apnData, $config->commands)
        ) {
            return ['status' => 2];
        }

        throw new \Exception(trans('validation.cant_configure_device'));
    }

    private function setAbleUsers($device = null)
    {
        if (isAdmin() && ! empty($this->data['user_id'])) {
            $this->data['user_id'] = array_combine($this->data['user_id'], $this->data['user_id']);

            if ($device && $this->user->isManager()) {
                $users = $this->user->subusers()->pluck('id', 'id')->all() + [$this->user->id => $this->user->id];

                foreach ($device->users as $user) {
                    if (array_key_exists($user->id, $users) && !array_key_exists($user->id, $this->data['user_id'])) {
                        unset($this->data['user_id'][$user->id]);
                    }

                    if (!array_key_exists($user->id, $users) && !array_key_exists($user->id, $this->data['user_id'])) {
                        $this->data['user_id'][$user->id] = $user->id;
                    }
                }
            }
        } else {
            unset($this->data['user_id']);
        }
    }
}