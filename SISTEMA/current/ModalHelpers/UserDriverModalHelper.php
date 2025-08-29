<?php namespace ModalHelpers;

use CustomFacades\Repositories\UserDriverRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Validators\UserDriverFormValidator;
use Illuminate\Support\Facades\DB;
use Tobuli\Exceptions\ValidationException;

use Tobuli\Entities\Device;

class UserDriverModalHelper extends ModalHelper
{
    public function get()
    {
        $this->checkException('drivers', 'view');

        $this->data['filter']['user_id'] = $this->user->id;
        $drivers = UserDriverRepo::searchAndPaginate($this->data, 'id', 'desc', 10);

        if ($this->api) {
            $drivers = $drivers->toArray();
            $drivers['url'] = route('api.get_user_drivers');
        }

        return compact('drivers');
    }

    public function createData()
    {
        $this->checkException('drivers', 'create');

        $devices = $this->user->devices;

        return compact('devices');
    }

    public function create()
    {
        $this->checkException('drivers', 'store');

        $this->validate('create');

        $driver = UserDriverRepo::create($this->data + ['user_id' => $this->user->id]);

        $driver->devices()->sync(array_get($this->data, 'devices', []));

        $setCurrent = array_get($this->data, 'current');
        $device_id = array_get($this->data, 'device_id');

        if ($setCurrent && $device = $this->user->devices()->find($device_id)) {
            $device->changeDriver($driver);
        }

        return ['status' => 1, 'item' => $driver];
    }

    public function editData()
    {
        $id = array_key_exists('user_driver_id', $this->data) ? $this->data['user_driver_id'] : request()->route('user_drivers');

        $item = UserDriverRepo::find($id);

        $this->checkException('drivers', 'edit', $item);

        $devices = $this->user->devices;

        return compact('item', 'devices');
    }

    public function edit()
    {
        $driver = UserDriverRepo::find($this->data['id']);

        $this->checkException('drivers', 'update', $driver);

        $setCurrent = array_get($this->data, 'current');
        $device_id = array_get($this->data, 'device_id');

        if ($setCurrent) {
            if ($device_id && $device = $this->user->devices()->find($device_id)) {
                $driver->changeDevice($device);
            } else {
                $driver->changeDevice(null);
            }
        }

        UserDriverRepo::update($driver->id, $this->data);
        $driver->devices()->sync(array_get($this->data, 'devices', []));

        return ['status' => 1];
    }

    public function editField($id)
    {
        $driver = UserDriverRepo::find($id);

        $this->checkException('drivers', 'update', $driver);

        $this->validate('silentUpdate');

        UserDriverRepo::update($driver->id, $this->data);

        return ['status' => 1];
    }

    private function validate($type)
    {
        UserDriverFormValidator::validate($type, $this->data);
    }

    public function doDestroy($id)
    {
        $item = UserDriverRepo::find($id);

        $this->checkException('drivers', 'remove', $item);

        return compact('item');
    }

    public function destroy()
    {
        $id = array_key_exists('user_driver_id', $this->data) ? $this->data['user_driver_id'] : $this->data['id'];
        $item = UserDriverRepo::find($id);

        $this->checkException('drivers', 'remove', $item);

        UserDriverRepo::delete($id);

        return ['status' => 1];
    }
}