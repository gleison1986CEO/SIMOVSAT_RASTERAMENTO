<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exceptions\PermissionException;
use App\Exceptions\ResourseNotFoundException;
use App\Transformers\Device\DeviceMapTransformer;
use CustomFacades\Repositories\UserRepo;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use ModalHelpers\DeviceModalHelper;
use Tobuli\Entities\User;
use Tobuli\Entities\Sharing;
use FractalTransformer;

class SharingController extends Controller
{
    private $deviceModalHelper;
    private $sharing;

    public function __construct(DeviceModalHelper $deviceModalHelper)
    {
        parent::__construct();

        $this->sharing = Sharing::where('hash', request()->route()->parameter('hash'))->first();

        if (!$this->sharing || !$this->sharing->isActive()) {
            throw new ResourseNotFoundException(trans('admin.map'));
        }

        if ( ! $this->sharing->user) {
            throw new ResourseNotFoundException(trans('global.user'));
        }

        if ( ! $this->sharing->user->perm('sharing', 'view')) {
            throw new PermissionException();
        }

        setActingUser($this->sharing->user);

        $this->deviceModalHelper = $deviceModalHelper;
    }

    public function index($hash)
    {
        $data = FractalTransformer::collection($this->sharing->activeDevices, DeviceMapTransformer::class)->toArray();

        $devices = $data['data'];


        return view('front::Layouts.sharing')->with(compact('devices'));
    }

    public function devices()
    {
        // acting user might be set in modal helper construct instead
        $this->deviceModalHelper->setUser($this->sharing->user);

        $activeDevices = $this->sharing->activeDevices;
        $ids = $activeDevices->pluck('id');

        return $this->itemsJson(request()->get('time'), $ids);
    }

    //  TODO: move out of here
    public function itemsJson($time = null, $activeDeviceIds)
    {
        if (!$time) {
            $time = time() - 5;
        }

        $time = intval($time);

        $devices = UserRepo::getDevicesHigherTime($this->sharing->user, $time, $activeDeviceIds);

        $items = [];

        foreach ($devices as $key => $item) {
            $items[$key] = $this->deviceModalHelper->generateJson($item, true, true);
        }

        return ['items' => $items, 'events' => [], 'time' => $time, 'version' => Config::get('tobuli.version')];
    }
}
