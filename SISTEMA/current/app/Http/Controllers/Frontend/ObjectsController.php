<?php namespace App\Http\Controllers\Frontend;

use App\Exceptions\PermissionException;
use App\Http\Controllers\Controller;
use App\Transformers\Device\DeviceMapTransformer;
use Curl;
use CustomFacades\ModalHelpers\DeviceModalHelper;
use CustomFacades\Repositories\DeviceGroupRepo;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\GeofenceGroupRepo;
use CustomFacades\Repositories\MapIconRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Server;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use ModalHelpers\SendCommandModalHelper;
use Tobuli\Entities\Device;
use Tobuli\Entities\PoiGroup;
use Tobuli\Repositories\Device\EloquentDeviceRepository;
use FractalTransformer;
use Formatter;

class ObjectsController extends Controller {

    const LOAD_LIMIT = 100;

    private $section = 'objects';
    /**
     * @var Device
     */
    private $device;
    /**
     * @var TraccarDevice
     */
    private $traccarDevice;
    /**
     * @var Event
     */
    private $event;

    function __construct( EloquentDeviceRepository $device)
    {
        parent::__construct();
        $this->device = $device;

        Server::setMemoryLimit(config('server.device_memory_limit'));
    }

    public function index()
    {
        $version = Config::get('tobuli.version');
        $devices = [];
        if ($this->user->perm('devices', 'view'))
            $devices = UserRepo::getDevices($this->user->id);

        if (!empty($devices))
        //BUSCAR POR PLACA //
            $devices = $devices->pluck('plate_number', 'id')->all();

        $history = [
            'start' => Formatter::time()->convert(date('Y-m-d H:i:s'), 'Y-m-d'),
            'end' => Formatter::time()->convert(date('Y-m-d H:i:s'), 'Y-m-d'),
            'end_time' => '23:45',
        ];

        $dashboard = $this->user->getSettings('dashboard.enabled');

        $mapIcons = MapIconRepo::all();

        $poi_groups = PoiGroup::where(['user_id' => $this->user->id])->get()
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $geofence_groups = GeofenceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $geofence_types = ['polygon' => trans('front.polygon'), 'circle' => trans('front.circle')];

        return view('front::Objects.index')
            ->with(
                compact('devices', 'history', 'version', 'geofence_groups',
                    'mapIcons', 'geofence_types', 'dashboard', 'poi_groups'
            ));
    }

    protected function getGroups()
    {
        if ( ! $this->user->perm('devices', 'view') )
            throw new PermissionException();

        $search = request()->get('s');

        $groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id], 'title')
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $groups_opened = json_decode($this->user->open_device_groups, TRUE);

        foreach ($groups as $group_id => & $group) {
            $title = $group;

            $query = $this->user->devices()->with(['traccar', 'sensors']);

            if ($search)
                $query->search($search);

            if ($group_id)
                $query->where('group_id', $group_id);
            else
                $query->whereNull('group_id');

            $group = [
                'id'      => $group_id,
                'title'   => $title,
                'open'    => ($groups_opened && in_array($group_id, $groups_opened)) || $search,
                'devices' => $query
                    ->paginate(self::LOAD_LIMIT)
                    ->appends([
                        'group_id' => $group_id,
                        's' => request()->get('s')]
                    )
            ];

            if ( ! $group['devices']->count())
                unset($groups[$group_id]);
        }

        return view('front::Objects.groups')->with(compact('groups'));
    }

    protected function getItems()
    {
        if ( ! $this->user->perm('devices', 'view') )
            throw new PermissionException();

        $search = request()->get('s');
        $group_id = request()->get('group_id');

        $query = $this->user->devices()->with(['traccar', 'sensors']);

        if ($search)
            $query->search($search);

        if ( ! is_null($group_id)) {
            if ($group_id)
                $query->where('group_id', $group_id);
            else
                $query->whereNull('group_id');
        }

        $devices = $query->paginate(self::LOAD_LIMIT)->appends(['group_id' => $group_id, 's' => request()->get('s')]);

        return view('front::Objects.items')->with(compact('devices'));
    }

    public function items() {
        if ( ! $this->user->perm('devices', 'view') )
            throw new PermissionException();

        if ( ! request()->wantsJson())
            return request()->filled('page') ? $this->getItems() : $this->getGroups();

        $devices = $this->user
            ->devices()
            ->filter(request()->all())
            ->wasConnected()
            ->with([
                'sensors',
                'services',
                'driver',
                'traccar',
                'icon'
            ])
            ->clearOrdersBy()
            ->paginate(500);

        return response()->json(
            FractalTransformer::paginate($devices, DeviceMapTransformer::class)->toArray()
        );
    }

    public function itemsSimple() {

        $deviceCollection = $this->user->devices()
            ->search(Input::get('search_phrase'))
            ->orderBy('plate_number', 'asc')
            ->paginate(15);

        return view('front::Objects.itemsSimple')->with(compact('deviceCollection'));
    }

    public function itemsJson() {
        $data = DeviceModalHelper::itemsJson();

        return $data;
    }

    public function changeGroupStatus() {
        if ( isDemoUser() )
            return;

        $device_groups_opened = array_flip(json_decode($this->user->open_device_groups, TRUE));

        if (isset($device_groups_opened[$this->data['id']])) {
            unset($device_groups_opened[$this->data['id']]);
            $device_groups_opened = array_flip($device_groups_opened);
        }
        else {
            $device_groups_opened = array_flip($device_groups_opened);
            array_push($device_groups_opened, $this->data['id']);
        }

        UserRepo::update($this->user->id, [
            'open_device_groups' => json_encode($device_groups_opened)
        ]);
    }

    public function changeAlarmStatus()
    {
        if (!array_key_exists('id', $this->data) && array_key_exists('device_id', $this->data))
            $this->data['id'] = $this->data['device_id'];
        $item = DeviceRepo::find($this->data['id']);
        if (empty($item) || (!$item->users->contains($this->user->id) && !isAdmin()))
            return ['status' => 0];

        $position = $item->positions()->orderBy('time', 'desc')->first();

        $sendCommandModalHelper = new SendCommandModalHelper();
        $sendCommandModalHelper->setData([
            'device_id' => $item->id,
            'type' => $item->alarm == 0 ? 'alarmArm' : 'alarmDisarm'
        ]);
        $result = $sendCommandModalHelper->gprsCreate();

        $alarm = $item->alarm;

        if ($result['status'] == 1) {
            $tr = TRUE;
            $times = 1;
            $val = '';
            if (isset($position)) {
                while($tr && $times < 6) {
                    $positions = $item->positions()->where('time', '>', $position->time)->orderBy('time', 'asc')->get();
                    if ($times >= 5)
                        $positions = $item->positions()->select('other')->orderBy('time', 'desc')->limit(2)->get();
                    foreach ($positions as $pos) {
                        preg_match('/<'.preg_quote('alarm', '/').'>(.*?)<\/'.preg_quote('alarm', '/').'>/s', $pos->other, $matches);
                        if (!isset($matches['1']))
                            continue;

                        $val = $matches['1'];
                        if ($val == 'lt' || $val == 'mt' || $val == 'lf') {
                            $tr = FALSE;
                            break;
                        }
                    }

                    $times++;
                    sleep(1);
                }
            }

            $status = 0;

            if (!$tr) {
                if (($item->alarm == 0 && $val == 'lt') || ($item->alarm == 1 && $val == 'mt')) {
                    $status = 1;
                    $alarm = $item->alarm == 1 ? 0 : 1;
                    DeviceRepo::update($item->id, [
                        'alarm' => $alarm
                    ]);
                }
            }

            return ['status' => $status, 'alarm' => intval($alarm), 'error' => trans('front.unexpected_error')];
        }
        else {
            return ['status' => 0, 'alarm' => intval($alarm), 'error' => isset($result['error']) ? $result['error'] : ''];
        }
    }

    public function alarmPosition()
    {
        $item = DeviceRepo::find($this->data['id']);
        if (empty($item) || (!$item->users->contains($this->user->id) && !isAdmin()))
            return response()->json(['status' => 0]);

        $sendCommandModalHelper = new SendCommandModalHelper();
        $sendCommandModalHelper->setData([
            'device_id' => $item->id,
            'type' => 'positionSingle'
        ]);
        $result = $sendCommandModalHelper->gprsCreate();

        if ($result['status'] == 1)
            return ['status' => 1];
        else
            return ['status' => 0, 'error' => isset($result['error']) ? $result['error'] : ''];
    }

    public function showAddress() {
        try {
            $location = \CustomFacades\GeoLocation::byAddress($this->data['address']);

            if ($location)
                return ['status' => 1, 'location' => $location->toArray()];

            return ['status' => 0, 'error' => trans('front.nothing_found_request')];
        } catch(\Exception $e) {
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }
}
