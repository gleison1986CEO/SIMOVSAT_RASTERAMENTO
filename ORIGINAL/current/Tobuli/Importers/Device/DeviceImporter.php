<?php

namespace Tobuli\Importers\Device;

use CustomFacades\Server;
use CustomFacades\ModalHelpers\SensorModalHelper;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\SensorGroupSensorRepo;
use CustomFacades\Repositories\TimezoneRepo;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\Importer;
use Validator;

class DeviceImporter extends Importer
{
    protected $device_icon_colors = [
        'green',
        'yellow',
        'red',
        'blue',
        'orange',
        'black',
    ];

    protected $defaults = [
        'visible'             => true,
        'active'              => true,
        'group_id'            => null,
        'icon_id'             => 0,
        'fuel_quantity'       => 0,
        'fuel_price'          => 0,
        'fuel_measurement_id' => 1,
        'min_moving_speed'    => 6,
        'min_fuel_fillings'   => 10,
        'min_fuel_thefts'     => 10,
        'tail_length'         => 5,
        'tail_color'          => '#33cc33',
        'timezone_id'         => null,
        'expiration_date'     => '0000-00-00 00:00:00',
        'gprs_templates_only' => false,
        'snap_to_road'        => false,
        'icon_colors'         => [
            'moving'  => 'green',
            'stopped' => 'yellow',
            'offline' => 'red',
            'engine'  => 'yellow',
        ],
    ];

    protected function importItem($data, $attributes = [])
    {
        $data = $this->mergeDefaults($data);
        $data = $this->normalize($data);

        if ( ! $this->validate($data)) {
            return;
        }

        $device = $this->getDevice($data);

        if ( ! $device) {
            if ($this->devicesLimit()) {
                return;
            }

            if ($this->usersDeviceLimit($data)) {
                return;
            }

            $this->create($data);
        }
    }

    private function normalize(array &$data): array
    {
        $users = $this->getUsers($data);

        if ($users) {
            $data['user_id'] = $users->pluck('id')->all();
        } else {
            $data['user_id'] = [auth()->user()->id];
        }

        if (empty($data['icon_id'])) {
            $data['icon_id'] = 0;
        }

        $data['fuel_per_km'] = convertFuelConsumption($data['fuel_measurement_id'], $data['fuel_quantity']);

        $statuses = ['moving', 'stopped', 'offline', 'engine'];

        foreach ($statuses as $status) {
            if (isset($data['icon_' . $status]) && in_array($data['icon_' . $status], $this->device_icon_colors)) {
                $data['icon_colors'][$status] = $data['icon_' . $status];
            }
        }

        if ( ! empty($data['timezone'])) {
            $timezone = $this->getTimezone($data['timezone']);

            $data['timezone_id'] = $timezone ? $timezone->id : null;
        }

        return $data;
    }

    private function getUsers($data)
    {
        if ( ! empty($data['user_id']) && is_string($data['user_id'])) {
            $data['user_id'] = explode(',', $data['user_id']);
        }

        if ( ! empty($data['users'])) {
            $emails = explode(',', $data['users']);
            $emails = array_map('trim', $emails);

            $users = User::whereIn('email', $emails)->get();

            $data['user_id'] = $users ? $users->pluck('id')->all() : [];
        }

        if (empty($data['user_id'])) {
            $data['user_id'] = [auth()->user()->id];
        }

        if (auth()->user()->isManager()) {
            $query = User::whereIn('id', $data['user_id'])->where('manager_id', auth()->user()->id);

            if (in_array(auth()->user()->id, $data['user_id'])) {
                $query->orWhere('id', auth()->user()->group_id);
            }

            return $query->get();
        }

        return User::whereIn('id', $data['user_id'])->get();
    }

    private function getTimezone($timezone)
    {
        return TimezoneRepo::findWhere(['title' => 'UTC ' . $timezone]);
    }

    private function getDevice($data)
    {
        return DeviceRepo::whereImei($data['imei']);
    }

    private function devicesLimit()
    {
        if (Server::hasDeviceLimit() && Server::getDeviceLimit() < Device::count())
        {
            throw new ValidationException(['id' => trans('front.devices_limit_reached')]);
        }

        return false;
    }

    private function usersDeviceLimit($data)
    {
        $users = $this->getUsers($data);

        foreach ($users as $user) {
            if ($this->userDevicesLimit($user)) {
                throw new ValidationException(['id' => $user->email . ': ' . trans('front.devices_limit_reached')]);
            }
        }

        return false;
    }

    private function userDevicesLimit($user)
    {
        if (is_null($user->devices_limit)) {
            return false;
        }

        if ($user->isManager()) {
            $user_devices_count = getManagerUsedLimit($user->id);
        } else {
            $user_devices_count = $user->devices->count();
        }

        if ($user_devices_count >= $user->devices_limit) {
            return true;
        }

        return false;
    }

    private function create($data)
    {
        beginTransaction();
        try {
            $device = DeviceRepo::create($data);

            $this->deviceSyncUsers($device, $data);

            $device->createPositionsTable();

            $this->createSensors($device, $data);

        } catch (\Exception $e) {
            rollbackTransaction();
            throw new ValidationException(['id' => $e->getMessage()]);
        }
        commitTransaction();
    }

    private function deviceSyncUsers($device, $data)
    {
        $device->users()->sync($data['user_id']);

        $group = $data['group_id'] ? DeviceGroup::find($data['group_id']) : null;

        // Filter User with group
        $users = array_filter($data['user_id'], function($user_id) use ($group){
            return $group && $user_id == $group->user_id;
        });

        if ($users) {
            DB::table('user_device_pivot')
                ->where('device_id', $device->id)
                ->whereIn('user_id', $users)
                ->update([
                    'group_id' => $group->id,
                    'active' => $data['visible'] ? true : false
                ]);
        }

        // Filter Users without group
        $users = array_filter($data['user_id'], function($user_id) use ($group){
            return ! $group || $user_id != $group->user_id;
        });

        if ($users) {
            DB::table('user_device_pivot')
                ->where('device_id', $device->id)
                ->whereIn('user_id', $users)
                ->update([
                    'group_id' => null,
                    'active' => $data['visible'] ? true : false
                ]);
        }
    }

    protected function createSensors($device, $data)
    {
        if ( ! isAdmin()) {
            return;
        }

        if ( ! isset($data['sensor_group_id'])) {
            return;
        }

        $group_sensors = SensorGroupSensorRepo::getWhere(['group_id' => $data['sensor_group_id']]);

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
                'user_id'     => $data['user_id'],
                'device_id'   => $device->id,
                'sensor_type' => $sensor['type'],
                'sensor_name' => $sensor['name'],
            ], $sensor));

            SensorModalHelper::create();
        }
    }

    public static function getValidationRules(): array
    {
        return [
            'imei'                => 'required',
            'name'                => 'required',
            'icon_id'             => 'required|exists:device_icons,id',
            'fuel_quantity'       => 'numeric',
            'fuel_price'          => 'numeric',
            'fuel_measurement_id' => 'required',
            'tail_length'         => 'required|numeric|min:0|max:10',
            'min_moving_speed'    => 'required|numeric|min:1|max:50',
            'min_fuel_fillings'   => 'required|numeric|min:1|max:1000',
            'min_fuel_thefts'     => 'required|numeric|min:1|max:1000',
            'group_id'            => 'nullable|exists:device_groups,id',
            'sim_number'          => 'unique:devices,sim_number',
            'timezone_id'         => 'nullable|exists:timezones,id',
        ];
    }

    protected function getDefaults()
    {
        return $this->defaults;
    }
}
