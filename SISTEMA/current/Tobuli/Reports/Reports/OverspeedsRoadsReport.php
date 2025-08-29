<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupOverspeedRoads;
use Tobuli\History\Actions\OverspeedRoads;
use Tobuli\History\Actions\Speed;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class OverspeedsRoadsReport extends DeviceHistoryReport
{
    const TYPE_ID = 59;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.overspeeds') . ' / ' . trans('front.road');
    }

    static public function isEnabled()
    {
        return !empty(config('services.speedlimit.key'));
    }

    protected function getActionsList()
    {
        return [
            Duration::class,
            Distance::class,
            Speed::class,
            OverspeedRoads::class,

            GroupOverspeedRoads::class,
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
                'overspeed_limit'
            ]);
        }

        return [
            'rows'   => $rows,
            'totals' => [],
        ];
    }
}