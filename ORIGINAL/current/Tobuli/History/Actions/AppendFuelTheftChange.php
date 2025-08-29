<?php

namespace Tobuli\History\Actions;

class AppendFuelTheftChange extends ActionAppend
{
    protected $sensors = [];

    static public function required()
    {
        return [
            AppendFuelChange::class,
            AppendFuelTanksDiff::class,
        ];
    }

    public function boot()
    {
        $this->min_fuel_thefts = $this->history->config('min_fuel_thefts');
    }

    public function proccess(& $position)
    {
        if (empty($position->fuel_change))
            return;

        foreach ($position->fuel_change as $sensor_id => $change) {
            if (empty($change['end']))
                continue;

            $sensor = $this->getSensorByID($sensor_id);
            $min_change = (-1) * $this->getMinFuelChange($sensor);

            // continue differance change
            if ($change['diff'] > $min_change)
                continue;

            // past time differance change
            if ($change['end']->fuel_tanks_diff[$sensor_id] > $min_change)
                continue;

            $prevTank = $change['start']->fuel_tanks[$sensor->id];
            $diff = $change['diff'];

            $position->fuel_theft = [
                'sensor_id' => $sensor->id,
                'previous'  => $prevTank,
                'current'   => $prevTank + $diff,
                'diff'      => $diff,
                'unit'      => $sensor->unit_of_measurement
            ];

            return;
        }
    }

    protected function getSensorByID($sensor_id) {
        return $this->getDevice()->sensors->firstWhere('id', $sensor_id);
    }

    protected function getMinFuelChange($sensor)
    {
        if ($this->min_fuel_thefts != 10)
            return $this->min_fuel_thefts;

        $max_tank = $sensor->getMaxTankValue();

        if ($max_tank < 100)
            return $this->min_fuel_thefts;

        return $max_tank * 0.1;
    }
}