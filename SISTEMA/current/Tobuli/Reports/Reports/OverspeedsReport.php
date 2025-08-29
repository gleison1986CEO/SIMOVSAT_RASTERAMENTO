<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupOverspeedStatic;
use Tobuli\History\Actions\OverspeedStatic;
use Tobuli\History\Actions\Speed;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class OverspeedsReport extends DeviceHistoryReport
{
    const TYPE_ID = 5;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.overspeeds');
    }

    protected function getActionsList()
    {
        return [
            Duration::class,
            Distance::class,
            Speed::class,
            OverspeedStatic::class,

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
            ]);
        }

        return [
            'rows'   => $rows,
            'totals' => [],
        ];
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return parent::getTotals($group, ['overspeed_count']);
    }
}