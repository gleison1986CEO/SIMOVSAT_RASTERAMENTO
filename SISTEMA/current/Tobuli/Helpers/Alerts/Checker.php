<?php

namespace Tobuli\Helpers\Alerts;

use Tobuli\Entities\Alert;

class Checker {

    protected $alerts;
    protected $device;

    public function __construct($device, $alerts)
    {
        $this->setDevice($device);
        $this->setAlerts($alerts);
    }

    public function setDevice($device)
    {
        $this->device = $device;
    }

    public function setAlerts($alerts)
    {
        $this->alerts = $alerts;
    }

    public function check($position = null, $prevPosition = null)
    {
        $events = [];

        if (empty($this->alerts))
            return [];

        foreach ($this->alerts as $alert)
        {
            if (empty($alert->type))
                continue;

            if ( ! $alert->user)
                continue;

            if ( ! $alert->user->isCapable())
                continue;

            $checker = $this->alertChecker($alert);

            if (empty($checker))
                continue;

            $checker->setCurrentPosition($position);
            $checker->setPreviousPosition($prevPosition);

            if ($_events = $checker->getEvents())
                $events = array_merge($events, $_events);
        }

        return $events;
    }

    public function alertChecker(Alert $alert) {
        switch($alert->type) {
            case 'overspeed':
                $checker = new OverspeedAlertCheck($this->device, $alert);
                break;
            case 'stop_duration':
                $checker = new StopDurationAlertCheck($this->device, $alert);
                break;
            case 'time_duration':
                $checker = new TimeDurationAlertCheck($this->device, $alert);
                break;
            case 'offline_duration':
                $checker = new OfflineDurationAlertCheck($this->device, $alert);
                break;
            case 'idle_duration':
                $checker = new IdleDurationAlertCheck($this->device, $alert);
                break;
            case 'ignition_duration':
                $checker = new IgnitionDurationAlertCheck($this->device, $alert);
                break;
            case 'move_start':
                $checker = new MoveStartAlertCheck($this->device, $alert);
                break;
            case 'geofence_in':
            case 'geofence_out':
            case 'geofence_inout':
                $checker = new GeofenceAlertCheck($this->device, $alert);
                break;
            case 'driver':
                $checker = new DriverAlertCheck($this->device, $alert);
                break;
            case 'driver_unauthorized':
                $checker = new DriverUnauthorizedAlertCheck($this->device, $alert);
                break;
            case 'custom':
                $checker = new EventCustomAlertCheck($this->device, $alert);
                break;
            case 'sos':
                $checker = new SosAlertCheck($this->device, $alert);
                break;
            case 'fuel_change':
                $checker = new FuelLevelChangeCheck($this->device, $alert);
                break;
            case 'distance':
                $checker = new DistanceAlertCheck($this->device, $alert);
                break;
            case 'poi_stop_duration':
                $checker = new PoiStopDurationAlertCheck($this->device, $alert);
                break;
            case 'poi_idle_duration':
                $checker = new PoiIdleDurationAlertCheck($this->device, $alert);
                break;
            default:
                throw new \Exception('Alert type "'.$alert->type.'" doesnt have check class.');
        }

        return $checker;
    }
}