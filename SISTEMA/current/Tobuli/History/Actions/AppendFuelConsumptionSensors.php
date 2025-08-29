<?php

namespace Tobuli\History\Actions;

class AppendFuelConsumptionSensors extends ActionAppend
{
    protected $sensors = [];

    protected $consumptions = [];

    static public function required()
    {
        return [];
    }

    public function boot()
    {
        $device = $this->getDevice();

        $this->sensors = $device->sensors
            ->filter(function($sensor) {
                return in_array($sensor->type, ['fuel_consumption']);
            });
    }

    public function proccess(& $position)
    {
        if (empty($position->consumptions))
            $position->consumptions = [];

        foreach ($this->sensors as $sensor)
            $position->consumptions[$sensor->id] = $this->getConsumptionValue($sensor, $position);
    }

    protected function getConsumptionValue($sensor, & $position)
    {
        $prevPosition = $this->getPrevPosition();

        if ( ! $prevPosition)
            return null;

        $value     = $this->getSensorValue($sensor, $position);
        $prevValue = $this->getSensorValue($sensor, $prevPosition);

        if (empty($value) || empty($prevValue))
            return 0;

        return $value - $prevValue;
    }
}