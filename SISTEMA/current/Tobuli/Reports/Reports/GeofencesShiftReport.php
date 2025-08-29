<?php

namespace Tobuli\Reports\Reports;

use Carbon\Carbon;
use Formatter;
use Tobuli\Entities\Geofence;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupGeofenceIn;
use Tobuli\Reports\DeviceHistoryReport;

class GeofencesShiftReport extends DeviceHistoryReport
{
    const TYPE_ID = 28;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.geofence_in_out').' (Shift)';
    }

    protected function getActionsList()
    {
        $list = [
            Duration::class,

            GroupGeofenceIn::class,
        ];

//        if ($this->zones_instead)
//            $list[] = GeofencesIn::class;

        return $list;
    }

    protected function getTable($data)
    {
        $parameters   = $this->parameters;
        $out_limit    = $parameters['excessive_exit'];
        $shift_start  = $parameters['shift_start'];
        $shift_finish = $parameters['shift_finish'];

        $late_entry   = Carbon::parse($shift_start)->addMinutes($parameters['shift_start_tolerance'])->format('H:i');
        $late_exit    = Carbon::parse($shift_finish)->subMinutes($parameters['shift_finish_tolerance'])->format('H:i');

        $rows = [];

        $result = [];

        foreach ($data['groups']->all() as $group)
        {
            $date = Formatter::time()->date($group->getStartPosition()->time);

            if (empty($result[$date][$group->geofence_id]))
            {
                $result[$date][$group->geofence_id] = [
                    'geofence' => runCacheEntity(Geofence::class, $group->geofence_id)->implode('name', ', '),
                    'shift'    => $late_entry . ' - ' . $late_exit,
                    'first_in' => $group->getStartAt(),
                    'last_out' => null,
                    'count' => 0,
                ];
            }

            $result[$date][$group->geofence_id]['last_out'] = $group->getEndAt();
            $result[$date][$group->geofence_id]['count']++;
        }

        foreach ($result as $day => $geofences) {
            $time_in  = strtotime($day . ' ' . $late_entry);
            $time_out = strtotime($day . ' ' . $late_exit);

            foreach ($geofences as $geofence_id => $values) {
                if ($values['count'] >= $out_limit ||
                    strtotime($values['first_in']) > $time_in ||
                    strtotime($values['last_out']) < $time_out)
                {
                    $rows[] = $result[$day][$geofence_id];
                }
            }
        }

        return [
            'rows'   => $rows,
            'totals' => [],
        ];
    }

    protected function isEmptyResult($data)
    {
        if (empty($data['root']->getStartPosition()))
            return true;

        $item = $this->getTable($data);

        return empty($item['table']['rows']);
    }
}