<?php namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use CustomFacades\Repositories\UserRepo;
use CustomFacades\Repositories\DeviceGroupRepo;
use CustomFacades\Validators\DeviceGroupFormValidator;
use Tobuli\Exceptions\ValidationException;

class DevicesGroupsController extends Controller
{
    public function index()
    {
        $this->checkException('devices_groups', 'view');

        $this->data['filter']['user_id'] = $this->user->id;

        $data = [
            'devices_groups' => $item = DeviceGroupRepo::searchAndPaginate($this->data, 'title', 'asc', 10),
        ];

        return view('front::DevicesGroups.index')->with($data);
    }

    public function table()
    {
        $this->checkException('devices_groups', 'view');

        $this->data['filter']['user_id'] = $this->user->id;

        $data = [
            'devices_groups' => $item = DeviceGroupRepo::searchAndPaginate($this->data, 'title', 'asc', 10),
        ];

        return view('front::DevicesGroups.table')->with($data);
    }

    public function create()
    {
        $this->checkException('devices_groups', 'create');

        $data = [
            'devices' => groupDevices($this->user->devices, $this->user),
        ];

        return view('front::DevicesGroups.create')->with($data);
    }

    public function store(Request $request)
    {
        $this->checkException('devices_groups', 'store');

        $data = array_merge($request->all(), ['user_id' => $this->user->id]);

        DeviceGroupFormValidator::validate('create', $data);

        $item = DeviceGroupRepo::create($data);

        if ( $devices = $request->input('devices', [])) {
            DB::table('user_device_pivot')
                ->where([
                    'user_id' => $this->user->id,
                ])
                ->whereIn('device_id', $devices)
                ->update([
                    'group_id' => $item->id,
                ]);
        }

        return response()->json(['status' => 1, 'id' => $item->id]);
    }

    public function edit($id)
    {
        $item = DeviceGroupRepo::find($id);

        $this->checkException('devices_groups', 'edit', $item);

        $data = [
            'item'   => $item,
            'devices' => groupDevices($this->user->devices, $this->user),
        ];

        return view('front::DevicesGroups.edit')->with($data);
    }

    public function update(Request $request, $id)
    {
        $item = DeviceGroupRepo::find($id);

        $this->checkException('devices_groups', 'update', $item);

        DeviceGroupFormValidator::validate('update', $request->all());

        $item->update($request->all());

        if ($request->has('devices')) {
            DB::table('user_device_pivot')
                ->where([
                    'user_id' => $this->user->id,
                    'group_id' => $item->id
                ])
                ->update([
                    'group_id' => null,
                ]);

            if ($devices = $request->input('devices', [])) {
                DB::table('user_device_pivot')
                    ->where([
                        'user_id' => $this->user->id,
                    ])
                    ->whereIn('device_id', $devices)
                    ->update([
                        'group_id' => $item->id,
                    ]);
            }
        }

        return response()->json(['status' => 1, 'id' => $item->id]);
    }

    public function doDestroy($id)
    {
        $item = DeviceGroupRepo::find($id);

        $this->checkException('devices_groups', 'remove', $item);

        $data = [
            'item' => $item,
        ];

        return view('front::DevicesGroups.destroy')->with($data);
    }

    public function destroy($id)
    {
        $item = DeviceGroupRepo::find($id);

        $this->checkException('devices_groups', 'remove', $item);

        $item->delete();

        return ['status' => 1];
    }
}
