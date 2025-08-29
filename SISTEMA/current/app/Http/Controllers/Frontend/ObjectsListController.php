<?php namespace App\Http\Controllers\Frontend;

use App\Exceptions\PermissionException;
use App\Http\Controllers\Controller;
use CustomFacades\Repositories\DeviceGroupRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Validators\ObjectsListSettingsFormValidator;
use Tobuli\Exceptions\ValidationException;
use Formatter;


class ObjectsListController extends Controller {

    public function __construct()
    {
        parent::__construct();

        if ( ! settings('plugins.object_listview.status'))
            throw new PermissionException();
    }

    public function index() {
        if ( ! settings('plugins.object_listview.status'))
            throw new PermissionException();

        $this->checkException('devices', 'view');

        if (request()->ajax())
            return view('front::ObjectsList.modal');
        else
            return view('front::ObjectsList.index');
    }

    public function items()
    {
        $this->checkException('devices', 'view');

        $device_groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $devices = UserRepo::getDevicesWith($this->user->id, [
            'devices',
            'devices.sensors',
            'devices.services',
            'devices.driver',
            'devices.traccar'
        ]);;

        $settings = UserRepo::getListViewSettings($this->user->id);

        $columns = $settings['columns'];
        $groupby = $settings['groupby'];

        $grouped = [];
        foreach ($devices as &$device)
        {
            $item = [];
            $address = null;

            $item['protocol'] = $device->protocol;
            $item['group'] = isset($device_groups[$device->pivot->group_id]) ? $device_groups[$device->pivot->group_id] : null;

            foreach ($columns as &$column) {
                if ($column['class'] == 'device') {
                    switch ($column['field']) {
                        case 'status':
                            if (empty($item['status']))
                                $item['status'] = $device->getStatus();
                            $item['status_color'] = $device->getStatusColor();
                            break;
                        case 'speed':
                            $item['speed'] = Formatter::speed()->human($device->getSpeed());
                            break;
                        case 'position':
                            $item['lat'] = $device->lat;
                            $item['lng'] = $device->lng;
                            break;
                        case 'address':
                            if (!$address) {
                                $item['lat'] = $device->lat;
                                $item['lng'] = $device->lng;

                                if ( $item['lat'] && $item['lng'] ) {
                                    $address = getGeoAddress( $item['lat'], $item['lng'] );
                                }
                            }
                            $item['address'] = $address;
                            break;
                        case 'fuel':
                            $sensor = $device->getFuelTankSensor();

                            if ($sensor)
                            {
                                $item['fuel'] = [
                                    'col1' => $sensor->getPercentage($device->traccar->other) . '%',
                                    'col2' => $sensor->getValueFormated($device->traccar->other),
                                    'col3' => $sensor->getValue($device->traccar->other) * $device->fuel_price,
                                ];
                            } else {
                                $item['fuel'] = [
                                    'col1' => '-',
                                    'col2' => '-',
                                    'col3' => '-',
                                ];
                            }
                            break;
                        case 'group':
                            break;
                        default:
                            $item[$column['field']] = $device->{$column['field']};
                    }
                } elseif ($column['class'] == 'sensor') {
                    $item[$column['field']] = null;

                    if ( $device->sensors ) {
                        foreach ($device->sensors as $sensor) {
                            if ($column['field'] == $sensor->hash) {
                                $column['title'] = $sensor->name;

                                $item[$column['field']] = $sensor->getValueFormated($device->traccar->other);

                                if (!empty($column['color'])) {
                                    foreach ($column['color'] as $color) {
                                        if ($sensor->value >= $color['from'] && $sensor->value <= $color['to']) {
                                            $item['color'][$column['field']] = $color['color'];
                                        }
                                    }
                                }

                                break;
                            }
                        }
                    }
                }

            }

            $grouped[ $item[$groupby] ][] = $item;
        }

        unset($devices);

        return view('front::ObjectsList.list')->with(compact('grouped','columns'));
    }

    public function edit()
    {
        $this->checkException('users', 'edit', $this->user);

        $numeric_sensors = config('tobuli.numeric_sensors');

        $settings = UserRepo::getListViewSettings($this->user->id);

        $fields = config('tobuli.listview_fields');

        listviewTrans($this->user->id, $settings, $fields);

        return view('front::ObjectsList.edit')->with(compact('fields','settings','numeric_sensors'));
    }

    public function update()
    {
        $this->checkException('users', 'update', $this->user);

        ObjectsListSettingsFormValidator::validate('update', $this->data);

        UserRepo::setListViewSettings($this->user->id, request()->all(['columns','groupby']));

        return ['status' => 1];
    }
}
