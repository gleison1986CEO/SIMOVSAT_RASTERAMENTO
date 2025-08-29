<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PermissionException;
use App\Exceptions\ResourseNotFoundException;
use CustomFacades\Validators\AdminDeviceTypeValidator;
use Illuminate\Support\Facades\Input;
use Tobuli\Entities\DeviceType;

class DeviceTypeController extends BaseController
{
    public function index()
    {
        $items = DeviceType::paginate(15);

        return view('admin::DeviceTypes.'.(request()->ajax() ? 'table' : 'index'), [
            'items' => $items,
        ]);
    }

    public function create()
    {
        return view('admin::DeviceTypes.create');
    }

    public function store()
    {
        AdminDeviceTypeValidator::validate('create', $this->data);

        $deviceType = DeviceType::create($this->data);

        $image = Input::file('image');
        $deviceType->saveImage($image);

        return response()->json(['status' => 1]);
    }

    public function edit($id)
    {
        $deviceType = DeviceType::find($id);

        if (! $deviceType) {
            throw new ResourseNotFoundException(trans('front.device_type'));
        }

        return view('admin::DeviceTypes.edit', [
            'item' => $deviceType,
        ]);
    }

    public function update()
    {
        AdminDeviceTypeValidator::validate('update', $this->data);

        $deviceType = DeviceType::find($this->data['id']);
        $deviceType->update($this->data);

        if ($image = Input::file('image')) {
            $deviceType->saveImage($image);
        }

        return response()->json(['status' => 1]);
    }

    public function destroy()
    {
        $deviceType = DeviceType::find($this->data['id'] ?? null);

        if (!$deviceType) {
            throw new ResourseNotFoundException(trans('front.device_type'));
        }

        $deviceType->delete();

        return response()->json(['status' => 1]);
    }
}
