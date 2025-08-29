<?php

namespace Tobuli\History\Actions;


class AppendOdometer extends ActionAppend
{
    protected $sensor;

    static public function required(){
        return [
            AppendDistanceGPS::class
        ];
    }

    public function boot()
    {
        $this->sensor = $this->getSensor('odometer');

        if ( ! $this->sensor)
            return;

        if ($this->sensor->odometer_value_by == 'virtual_odometer')
        {
            $device = $this->getDevice();

            $result = $device->getSumDistance($this->getDateFrom());

            $this->sensor->odometer_value = round($this->sensor->odometer_value - $result);
        }
    }

    public function proccess(&$position)
    {
        $position->odometer = null;

        if ( ! $this->sensor)
            return;

        if ($this->sensor->odometer_value_by == 'virtual_odometer')
            $this->sensor->odometer_value += $position->distance_gps;

        $position->odometer = $this->getSensorValue($this->sensor, $position);
    }
}