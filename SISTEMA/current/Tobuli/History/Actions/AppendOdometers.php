<?php

namespace Tobuli\History\Actions;


class AppendOdometers extends ActionAppend
{
    protected $sensors;

    static public function required(){
        return [
            AppendDistanceGPS::class,
        ];
    }

    public function boot()
    {
        $device = $this->getDevice();

        $this->sensors = $device->getSensorsByType('odometer');

        if ( ! $this->sensors)
            return;

        foreach ($this->sensors as & $sensor)
        {
            if ($sensor->odometer_value_by != 'virtual_odometer')
                continue;

            $result = $device->getSumDistance($this->getDateFrom());

            $sensor->odometer_value = round($sensor->odometer_value - $result);
        }
    }

    public function proccess(&$position)
    {
        $position->odometers = [];

        if ( ! $this->sensors)
            return;

        foreach ($this->sensors as & $sensor)
        {
            if ($sensor->odometer_value_by == 'virtual_odometer')
                $sensor->odometer_value += $position->distance_gps;

            $position->odometers[$sensor->id] = $this->getSensorValue($sensor, $position);
        }
    }
}