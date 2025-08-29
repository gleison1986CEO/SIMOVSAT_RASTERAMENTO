<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use CustomFacades\ModalHelpers\AlertModalHelper;

class AlertsController extends Controller
{
    public function index()
    {
        $data = AlertModalHelper::get();

        return !$this->api ? view('front::Alerts.index')->with($data) : ['status' => 1, 'items' => $data];
    }

    public function create()
    {
        $data = AlertModalHelper::createData();

        return is_array($data) && !$this->api ? view('front::Alerts.create')->with($data) : $data;
    }

    public function store()
    {
        return AlertModalHelper::create();
    }

    public function edit()
    {
        $data = AlertModalHelper::editData();

        return is_array($data) && !$this->api ? view('front::Alerts.edit')->with($data) : $data;
    }

    public function update()
    {
        return AlertModalHelper::edit();
    }

    public function changeActive()
    {
        return AlertModalHelper::changeActive();
    }

    public function doDestroy($id) {
        $data = AlertModalHelper::doDestroy($id);

        return is_array($data) ? view('front::Alerts.destroy')->with($data) : $data;
    }

    public function destroy()
    {
        return AlertModalHelper::destroy();
    }

    public function getCommands()
    {
        return AlertModalHelper::getCommands();
    }

    public function syncDevices()
    {
        return AlertModalHelper::syncDevices();
    }

    public function summary()
    {
        $data = AlertModalHelper::summary(request()->get('date_from'), request()->get('date_to'));

        return ['status' => 1, 'items' => $data];
    }
}