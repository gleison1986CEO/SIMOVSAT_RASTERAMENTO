<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\Fuel;
use Tobuli\History\Actions\GroupDriveBusinessPrivate;
use Tobuli\History\Actions\Odometer;
use Tobuli\History\Actions\Speed;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class TravelSheetBusinessPrivateReport extends DeviceHistoryReport
{
    const TYPE_ID = 61;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.travel_sheet') . ' ('.trans('front.business').'/'.trans('front.private').')';
    }

    public static function isEnabled()
    {
        return settings('plugins.business_private_drive.status');
    }

    protected function getActionsList()
    {
        return [
            DriveStop::class,
            Duration::class,
            Distance::class,
            Speed::class,
            Fuel::class,
            Drivers::class,
            Odometer::class,

            GroupDriveBusinessPrivate::class,
        ];
    }

    protected function getTable($data)
    {
        $drive_types = [
            'drive_business' => trans('front.business'),
            'drive_private'  => trans('front.private'),
            'drive'          => trans('front.not_available'),
        ];

        $rows = [];

        foreach ($data['groups']->all() as $group)
        {
            $row = $this->getDataFromGroup($group, [
                'group_key',
                'start_at',
                'end_at',
                'duration',
                'distance',
                'drivers',
                'speed_max',
                'speed_avg',
                'location_start',
                'location_end',
                'fuel_consumption_list',
                'fuel_price_list',
                'odometer'
            ]);

            $row['drive_type'] = $drive_types[$group->getKey()] ?? null;

            $row['odometer_start'] = trans('front.not_available');
            $row['odometer_end']   = trans('front.not_available');
            $odometer = $group->stats()->has('odometer') ? $group->stats()->get('odometer') : null;

            if ($odometer) {
                $startPosition = $group->getStartPosition();
                if ($startPosition && isset($startPosition->odometer)) {
                    $odometer->set($startPosition->odometer);
                    $row['odometer_start'] = $odometer->human();
                }

                $endPosition = $group->getEndPosition();
                if ($endPosition && isset($endPosition->odometer)) {
                    $odometer->set($endPosition->odometer);
                    $row['odometer_end'] = $odometer->human();
                }
            }

            $rows[] = $row;
        }

        return [
            'rows'   => $rows,
            'totals' => $this->getDataFromGroup($data['groups']->merge(), [
                'duration',
                'distance',
                'fuel_consumption_list',
                'fuel_price_list'
            ]),
        ];
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return parent::getTotals($group, ['distance', 'drive_duration']);
    }
}