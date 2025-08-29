<?php

namespace Tobuli\Reports\Reports;

use Formatter;
use Carbon\Carbon;
use Tobuli\Reports\DeviceReport;

class OfflineDeviceReport extends DeviceReport
{
    protected $offline_timeout;

    const TYPE_ID = 38;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.offline_objects');
    }

    public static function isEnabled() {
        return settings('plugins.offline_objects_report.status');
    }

    protected function beforeGenerate() {
        parent::beforeGenerate();

        $this->offline_timeout = settings('main_settings.default_object_online_timeout') * 60;

        $this->date_from = Carbon::now();
        $this->date_to   = Carbon::now();

        if (empty($this->devicesQuery)) {
            $this->setDevicesQuery($this->user->devices());
        }
    }

    protected function generateDevice($device)
    {
        $offline_duration = ($device->last_connect_timestamp != 0) ? time() - $device->last_connect_timestamp : 0;

        if ($offline_duration < $this->offline_timeout)
            return [
                'meta' => $this->getDeviceMeta($device),
                'error' => trans('front.nothing_found_request')
            ];

        $odometer = null;
        $engine_hours = null;

        $odometer_sensor = $device->getOdometerSensor();
        $engine_hours_sensor = $device->getEngineHoursSensor();

        if ( ! is_null($odometer_sensor))
            $odometer = $odometer_sensor->getValue($device->other);

        if ( ! is_null($engine_hours_sensor))
            $engine_hours = $engine_hours_sensor->getValue($device->other);

        $distance = $device->getParameter('totaldistance') / 1000;
        $vEngineHours = $device->getParameter('enginehours');

        return [
            'meta' => $this->getDeviceMeta($device),
            'data' => [
                'time'             => Formatter::time()->human($device->lastConnectTime),
                'speed'            => Formatter::speed($device->speed),
                'altitude'         => Formatter::altitude($device->altitude),
                'course'           => $device->course,
                'offline_duration' => Formatter::duration($offline_duration),
                'odometer'         => $odometer ? Formatter::distance($odometer) : '',
                'engine_hours'     => $engine_hours ? Formatter::duration($engine_hours) : '',
                'location'         => $this->getLocation($device, $this->getAddress($device)),
                'distance'         => Formatter::distance($distance),
                'vEngineHours'     => Formatter::duration($vEngineHours),
            ]
        ];
    }
}