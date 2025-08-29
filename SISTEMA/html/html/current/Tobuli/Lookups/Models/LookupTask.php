<?php

namespace Tobuli\Lookups\Models;

use Tobuli\Entities\Task;
use Tobuli\Lookups\LookupModel;

class LookupTask extends LookupModel
{
    protected function modelClass()
    {
        return Task::class;
    }

    protected function listColumns()
    {
        $this->addColumn('id');
        $this->addColumn('title');

        $this->addColumn([
            'data'       => 'user',
            'name'       => 'user.email',
            'title'      => trans('global.user'),
            'orderable'  => true,
            'searchable' => true,
            'relations'  => ['user']
        ]);

        $this->addColumn([
            'data'       => 'device',
            'name'       => 'device.name',
            'title'      => trans('validation.attributes.device_id'),
            'orderable'  => true,
            'searchable' => true,
            'relations'  => ['device']
        ]);

        $this->addColumn('comment');
        $this->addColumn('priority');
        $this->addColumn('status');
        $this->addColumn('invoice_number');

        $this->addColumn([
            'data'       => 'pickup_address',
            'name'       => 'pickup_address',
            'title'      => trans('validation.attributes.pickup_address'),
            'orderable'  => false,
            'searchable' => false
        ]);
        $this->addColumn('pickup_time_from');
        $this->addColumn('pickup_time_to');

        $this->addColumn([
            'data'       => 'delivery_address',
            'name'       => 'delivery_address',
            'title'      => trans('validation.attributes.delivery_address'),
            'orderable'  => false,
            'searchable' => false
        ]);
        $this->addColumn('delivery_time_from');
        $this->addColumn('delivery_time_to');

        if (config('addon.custom_fields_task')) {
            $this->addCustomFields(new Task());
        }
    }

    public function renderHtmlPriority(Task $task)
    {
        return $task->priority_name;
    }

    public function renderHtmlStatus(Task $task)
    {
        return $task->status_name;
    }

    public function renderPickupAddress(Task $task)
    {
        if ($task->pickup_address) {
            return $task->pickup_address;
        }

        if ($task->pickup_address_lat !== null && $task->pickup_address_lng !== null) {
            return getGeoAddress($task->pickup_address_lat, $task->pickup_address_lng);
        }

        return null;
    }

    public function renderDeliveryAddress(Task $task)
    {
        if ($task->delivery_address) {
            return $task->delivery_address;
        }

        if ($task->delivery_address_lat !== null && $task->delivery_address_lng !== null) {
            return getGeoAddress($task->delivery_address_lat, $task->delivery_address_lng);
        }

        return null;
    }
}