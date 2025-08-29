<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\AppendOverspeedGeofenceOnly;
use Tobuli\History\Actions\BreakGeofenceIn;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GeofencesIn;
use Tobuli\History\Actions\GroupOverspeedStatic;
use Tobuli\History\Actions\OverspeedStatic;
use Tobuli\History\Actions\Speed;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class OverspeedsInGeofenceReport extends DeviceHistoryReport
{
    const TYPE_ID = 47;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return  trans('front.overspeeds') . ' / ' . trans('front.geofences');
    }

    protected function getActionsList()
    {
        return [
            Duration::class,
            Distance::class,
            Speed::class,
            OverspeedStatic::class,
            GeofencesIn::class,
            Drivers::class,
            AppendOverspeedGeofenceOnly::class,

            GroupOverspeedStatic::class,
        ];
    }

    protected function getTable($data)
    {
        $rows = [];

        foreach ($data['groups']->all() as $group)
        {
            $rows[] = $this->getDataFromGroup($group, [
                'start_at',
                'end_at',
                'duration',
                'speed_max',
                'speed_avg',
                'location',
                'geofences_in',
                'drivers'
            ]);
        }

        return [
            'rows'   => $rows,
            'totals' => [],
        ];
    }

    protected function isEmptyResult($data)
    {
        return empty($data['groups']) || empty($data['groups']->all());
    }

    protected function generateDevice($device)
    {
        $data = $this->getDeviceHistoryData($device);

        if ($this->isEmptyResult($data))
            return null;

        return [
            'meta' => $this->getDeviceMeta($device) + $this->getHistoryMeta($data['root']),
            'table'  => $this->getTable($data),
            'totals' => $this->getTotals($data['groups']->merge())
        ];
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return parent::getTotals($group, ['overspeed_count']);
    }
}