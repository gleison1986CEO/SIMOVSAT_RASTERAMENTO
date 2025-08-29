<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\GroupGeofenceGroupShifts;
use Tobuli\History\Actions\GroupGeofenceShifts;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class GeofencesStopShiftReport extends DeviceHistoryReport
{
    const TYPE_ID = 68;

    public $table = [];
    public $tableTotals = [];
    public $columns = [];

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.geofence_stop_count_shift');
    }

    protected function getActionsList()
    {
        return [
            DriveStop::class,
            $this->parameters['group_geofences'] ? GroupGeofenceGroupShifts::class : GroupGeofenceShifts::class,
        ];
    }

    protected function afterGenerate()
    {
        $this->tableTotals['shift_name'] = trans('global.total');

        foreach ($this->table as $name => &$shift) {
            foreach ($shift as $key => $value) {
                if (!isset($this->tableTotals[$key])) {
                    $this->tableTotals[$key] = 0;
                }
                
                $this->tableTotals[$key] += $value;
            }

            $shift = ['shift_name' => $name] + $shift;
        }

        $this->columns['shift_name'] = trans('front.shift_time');

        if ($this->parameters['group_geofences']) {
            foreach ($this->geofences as $geofence) {
                if ($group = $geofence->group) {
                    $this->columns[$group->id] = $group->title;
                } else {
                    $this->columns[0] = trans('front.ungrouped');
                }
            }
        } else {
            foreach ($this->getGeofences() as $geofence) {
                $this->columns[$geofence->id] = $geofence->name;
            }
        }

        ksort($this->columns);

        $this->columns['total'] = trans('global.total');
    }

    protected function generateDevice($device)
    {
        $data = $this->getDeviceHistoryData($device);

        if (empty($this->table)) {
            foreach (GroupGeofenceShifts::getShifts() as $shift) {
                $this->table[$shift['name']] = [
                    'total' => 0,
                ];
            }
        }

        /** @var Group $group */
        foreach ($data['groups']->all() as $group) {
            $shiftName = GroupGeofenceShifts::getShiftFromGroupName($group->getKey());
            $geofence = GroupGeofenceShifts::getGeofenceFromGroupName($group->getKey());

            if (empty($this->table[$shiftName])) {
                continue;
            }

            if (!isset($this->table[$shiftName][$geofence])) {
                $this->table[$shiftName][$geofence] = 0;
            }

            if ($group->stats()->get('stop_count')->value()) {
                $this->table[$shiftName][$geofence]++;
                $this->table[$shiftName]['total']++;
            }
        }
    }
}