<?php namespace Tobuli\Repositories\User;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\User as Entity;
use Tobuli\Repositories\EloquentRepository;

class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface {

    public function __construct( Entity $entity )
    {
        $this->entity = $entity;
    }

    public function searchAndPaginate(array $data, $sort_by, $sort = 'asc', $limit = 10)
    {
        $data = $this->generateSearchData($data);
        $sort = array_merge([
            'sort' => $sort,
            'sort_by' => $sort_by
        ], $data['sorting']);

        $query = $this->entity
            ->orderBy($sort['sort_by'], $sort['sort'])
            ->withCount('subusers')
            ->withCount('devices')
            ->with('manager:id,email')
            ->with('billing_plan:id,title')
            ->search($data['search_phrase'])
            ->filter($data['filter'])
            ->where(function ($query) {
                if (Auth::User()->isManager()) {
                    $query->where("users.manager_id", Auth::User()->id);
                }
            })
            ->groupBy('users.id');

        if (!empty($data['search_device'])) {
            $query->whereHas('devices', function($q) use ($data){
                $q->where('devices.imei', 'LIKE', "%".$data['search_device']."%");
            });
        }

        $items = $query->paginate($limit);

        if ($items->currentPage() > $items->lastPage()) {
            $items = $query->paginate($limit, ['*'], 'page', 1);
        }

        $items->sorting = $sort;

        return $items;
    }

    protected function generateSearchData($data)
    {
        return array_merge([
            'sorting' => [],
            'search_phrase' => '',
            'search_device',
            'filter' => []
        ], $data);
    }

    public function getOtherManagers($user_id) {
        return $this->entity->whereIn('group_id', [3, 5])->where('id', '!=', $user_id)->get();
    }

    public function getDevicesWithServices($user_id, $imei = null) {
        $query = $this->entity
            ->with('devices.sensors', 'devices.services')
            ->find($user_id)
            ->devices()
            ->has('services');

        if ($imei) {
            $query->where('devices.imei', $imei);
        }

        return $query->get();
    }

    public function getDevicesWith($user_id, $with) {
        return $this->entity->with($with)->find($user_id)->devices;
    }

    public function getDevicesWithWhere($user_id, $with, $where) {
        return $this->entity->with($with)->find($user_id)->devices;
    }

    public function getDevices($user_id) {
        return $this->entity->with('devices')->find($user_id)->devices;
    }

    public function getDevice($user_id, $device_id) {
        $user = $this->entity->find($user_id);

        if (!$user)
            return null;

        return $user->devices()->with('sensors', 'services')->find($device_id);
    }

    public function getDevicesHigherTime(Entity $user, $time, $devices = null)
    {
        $query = $user->devices()
            ->with(['sensors', 'services', 'driver', 'traccar', 'icon'])
            ->connectedAfter(date('Y-m-d H:i:s', $time));

        if ($devices) {
            $query->whereIn('devices.id', $devices);
        }

        return $query->get();
    }

    public function getDevicesSms($user_id) {
        return $this->entity->with('devices_sms')->find($user_id)->devices_sms;
    }

    public function getUsers($user)
    {
        if ($user->isAdmin())
            return $this->entity->orderby('email')->get();

        if ($user->isManager())
            return $this->entity->where('manager_id', $user->id)->orWhere('id', $user->id)->orderby('email')->get();

        return $this->entity->where('id', $user->id)->orderby('email')->get();
    }

    public function getDrivers($user_id) {
        return $this->entity->with('drivers')->find($user_id)->drivers;
    }

    public function getSettings($user_id, $key) {
        return $this->entity->find($user_id)->getSettings($key);
    }

    public function setSettings($user_id, $key, $value) {
        return $this->entity->find($user_id)->setSettings($key, $value);
    }

    public function getListViewSettings($user_id)
    {
        if (!is_null($user_id))
            $settings = $this->getSettings($user_id, 'listview');

        $fields_trans  = config('tobuli.listview_fields_trans');
        $sensors_trans = config('tobuli.sensors');

        $defaults = config('tobuli.listview');

        $settings = empty($settings) ? $defaults : array_merge($defaults, $settings);

        foreach($settings['columns'] as &$column) {
            if ( ! empty($column['class']) && $column['class'] == 'sensor') {
                $column['title'] = htmlentities( $sensors_trans[ $column['type'] ], ENT_QUOTES);
            } else {
                $column['class'] = 'device';
                $column['title'] = htmlentities( $fields_trans[ $column['field'] ], ENT_QUOTES);
            }
        }

        return $settings;
    }

    public function setListViewSettings($user_id, $settings)
    {
        return $this->setSettings($user_id, 'listview', $settings);
    }
}