<?php

namespace Tobuli\History\Actions;


class SensorsValues extends ActionStat
{
    static public function required()
    {
        return [
            AppendDuration::class
        ];
    }

    public function boot()
    {
        if ( ! $this->history->sensors)
            return;

        foreach ($this->history->sensors as &$sensor) {

            if ($sensor->odometer_value_by === 'virtual_odometer') {
                $result = $this->getDevice()->getSumDistance($this->getDateFrom());

                $sensor->odometer_value = round($sensor->odometer_value - $result);
            }

            $this->history->sensors_data[$sensor->id] = [
                'id' => $sensor->id,
                'name' => $sensor->formatName(),
                'unit' => $sensor->unit_of_measurement,
                'values' => []
            ];
        }
    }

    public function proccess($position)
    {
        if ( ! $this->history->sensors)
            return;

        foreach ($this->history->sensors as $sensor) {
            if ($sensor->odometer_value_by == 'virtual_odometer')
                $sensor->odometer_value += $position->distance_gps;

            $this->history->sensors_data[$sensor->id]['values'][$position->timestamp] = [
                //'i' => $position->id,
                't' => $position->timestamp,
                'v' => $this->getSensorValue($sensor, $position),
            ];
        }
    }
}