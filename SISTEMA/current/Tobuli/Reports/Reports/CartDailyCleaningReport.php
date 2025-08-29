<?php

namespace Tobuli\Reports\Reports;

use Formatter;
use Tobuli\Entities\Geofence;
use Tobuli\History\Actions\AppendDateUserZone;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\FirstDrive;
use Tobuli\History\Actions\GeofencesIn;
use Tobuli\History\Actions\GroupDaily;
use Tobuli\History\Actions\GroupDailySplit;
use Tobuli\History\Actions\GroupGeofenceIn;
use Tobuli\History\Actions\LastDrive;
use Tobuli\History\Actions\SpeedCondition;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class CartDailyCleaningReport extends DeviceHistoryReport
{
    const TYPE_ID = 60;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.cart_daily_cleaning');
    }

    public static function isEnabled()
    {
        return settings('plugins.report_cart_cleaning_daily.status');
    }

    protected function getActionsList()
    {
        return [
            AppendDateUserZone::class,
            Duration::class,
            Distance::class,
            GeofencesIn::class,
            SpeedCondition::class,
            FirstDrive::class,
            LastDrive::class,

            GroupDailySplit::class,
            GroupDaily::class,
            GroupGeofenceIn::class,
        ];
    }

    protected function getTable($data)
    {
        $rows = [];
        $total = new Group('device_total');
        $activeDays = 0;

        $groups = $data['groups']->all();

        foreach ($groups as $key => $group) {
            $date = Formatter::time()->date($group->getStartPosition()->time);

            if (empty($rows[$date])) {
                $rows[$date] = [
                    'date'           => $date,
                    'shift_start'    => $this->parameters['shift_start'] ?? '05:30',
                    'start_time'     => Formatter::time()->time($group->getStartPosition()->time),
                    'end_time'       => Formatter::time()->time($group->getEndPosition()->time),
                    'geofences'      => [],
                ];
            }

            if (starts_with($group->getKey(), 'geofence_in')) {
                $rows[$date]['geofences'][] = [
                    'name'       => runCacheEntity(Geofence::class, $group->geofence_id)->implode('name', ', '),
                    'enter_time' => Formatter::time()->time($group->getStartPosition()->time),
                    'leave_time' => Formatter::time()->time($group->getEndPosition()->time),
                ];
            } else {
                $day_distance = $group->stats()->get('speed_below_distance')->value();
                $min_distance = $this->parameters['distance_limit'] * 0.001 ?? 0;
                $activeDay    = $day_distance > $min_distance;
                $activeDays  +=  $activeDay ? 1 : 0;

                if ($activeDay) {

                    $total->applyArray($group->stats()->all());

                    $rows[$date] = array_merge($rows[$date], [
                        'start_time' => Formatter::time()->time($group->getStartPosition()->time),
                        'end_time' => Formatter::time()->time($group->getEndPosition()->time),
                        'speed_below_distance' => $group->stats()->human('speed_below_distance'),
                        'speed_below_duration' => $group->stats()->human('speed_below_duration'),
                        'speed_above_distance' => $group->stats()->human('speed_above_distance'),
                        'speed_above_duration' => $group->stats()->human('speed_above_duration'),
                    ]);
                } else {
                    $rows[$date] = array_merge($rows[$date], [
                        'error' => trans('front.nothing_found_request')
                    ]);
                }
            }
        }

        return [
            'rows'   => $rows,
            'totals' => [
                'active_days' => $activeDays,
                'speed_below_distance' => $total->stats()->human('speed_below_distance'),
                'speed_below_duration' => $total->stats()->human('speed_below_duration'),
                'speed_above_distance' => $total->stats()->human('speed_above_distance'),
                'speed_above_duration' => $total->stats()->human('speed_above_duration'),
            ],
        ];
    }
}