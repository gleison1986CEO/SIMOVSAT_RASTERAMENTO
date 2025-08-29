<?php

namespace Tobuli\Reports\Reports;

use Formatter;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupDailySplit;
use Tobuli\History\Actions\GroupEngineStatus;
use Tobuli\History\Actions\Speed;
use Tobuli\History\Group;

class IgnitionOnOff24ModeReport extends GeofencesInOutReport
{
    const TYPE_ID = 30;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.ignition_on_off');
    }

    protected function getActionsList()
    {
        return [
            Speed::class,
            Distance::class,
            Duration::class,
            Drivers::class,

            GroupDailySplit::class,
            GroupEngineStatus::class,
        ];
    }

    protected function isEmptyResult($data)
    {
        return empty($data['groups']) || empty($data['groups']->all());
    }

    protected function getTable($data)
    {
        $current_date = null;

        $rows = [];

        foreach ($data['groups']->all() as $group)
        {
            $datetime = $group->getStartPosition()->time;

            $date = Formatter::time()->date($datetime);
            $time = Formatter::time()->time($datetime);

            if ($current_date != $date)
            {
                $rows[] = [
                    'group_key' => 'date',
                    'date' => $date,
                ];

                $current_date = $date;
            }

            $row = $this->getDataFromGroup($group, [
                'group_key',
                'duration',
                'distance',
                'speed_avg',
                'drivers',
                'location',
            ]);

            $row['time'] = $time;

            $rows[] = $row;
        }

        return [
            'rows'   => $rows,
            'totals' => $this->getDataFromGroup($data['groups']->merge(), [
                'duration',
                'distance',
            ]),
        ];
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return [];
    }
}