<?php

namespace Tobuli\History\Actions;

use Formatter;
use Tobuli\History\Stats\StatConsumption;
use Tobuli\History\Stats\StatValue;
use Tobuli\History\Stats\StatValueFirst;

class Fuel extends ActionStat
{
    protected $able = false;

    protected $fuel_price;

    protected $total_by;

    static public function required()
    {
        return [
            AppendFuelConsumptions::class
        ];
    }

    public function boot()
    {
        $this->loadConsumptionGPS();
        $this->loadConsumptionSensors();

        if ($this->able) {
            $this->registerStat("fuel_consumption", (new StatConsumption())->setFormatUnit(Formatter::capacity()));

            if ($this->fuel_price) {
                $this->registerStat("fuel_price", (new StatConsumption()));
            }
        }
    }

    public function proccess($position)
    {
        $this->processFuelTank($position);
        $this->processConsumptions($position);
    }

    protected function processFuelTank($position)
    {
        if (empty($position->fuel_tanks))
            return;

        foreach ($position->fuel_tanks as $key => $value)
        {
            $this->history->applyStat("fuel_level_start_{$key}", $value);
            $this->history->applyStat("fuel_level_end_{$key}", $value);
        }
    }

    protected function processConsumptions($position)
    {
        if ( ! $position->consumptions)
            return;

        $consumption = null;

        foreach ($position->consumptions as $key => $value)
        {
            $this->history->applyStat("fuel_consumption_{$key}", $value);

            if ($this->fuel_price) {
                $this->history->applyStat("fuel_price_{$key}", $value * $this->fuel_price);
            }

            if ( ( $this->total_by == $key ) || ($this->total_by != 'gps' && $key != 'gps'))
                $consumption += $value;
        }

        if ( ! is_null($consumption)) {
            $this->history->applyStat("fuel_consumption", $consumption);

            if ($this->fuel_price)
                $this->history->applyStat("fuel_price", $consumption * $this->fuel_price);
        }
    }

    protected function loadConsumptionGPS()
    {
        $device = $this->getDevice();

        $this->fuel_price = (float)($device->fuel_price) * ($device->fuel_measurement_id == 2 ? 0.264172053 : 1);

        if ( $device->fuel_per_km > 0)
        {
            $this->able = true;
            $this->total_by = 'gps';

            $stat = (new StatConsumption())->setFormatUnit(Formatter::capacity());
            $stat->setName('GPS');

            $this->registerStat("fuel_consumption_gps", $stat);

            if ($this->fuel_price) {
                $stat = new StatConsumption();
                $stat->setName('GPS');
                $this->registerStat("fuel_price_gps", $stat);
            }
        }
    }

    protected function loadConsumptionSensors()
    {
        $device = $this->getDevice();

        foreach ($device->sensors as $sensor)
        {
            if ( ! in_array($sensor->type, ['fuel_tank', 'fuel_tank_calibration', 'fuel_consumption']))
                continue;

            $this->able = true;
            $this->total_by = 'sensor';

            $fuelFormatter = clone Formatter::capacity();
            $fuelFormatter->setUnit($sensor->unit_of_measurement);

            $name = $sensor->formatName();

            $stat = (new StatConsumption())->setFormatUnit($fuelFormatter);
            $stat->setName($name);
            $this->registerStat("fuel_consumption_{$sensor->id}", $stat);

            $stat = (new StatValueFirst())->setFormatUnit($fuelFormatter);
            $stat->setName($name);
            $this->registerStat("fuel_level_start_{$sensor->id}", $stat);

            $stat = (new StatValue())->setFormatUnit($fuelFormatter);
            $stat->setName($name);
            $this->registerStat("fuel_level_end_{$sensor->id}", $stat);

            if ($this->fuel_price) {
                $stat = new StatConsumption();
                $stat->setName($name);
                $this->registerStat("fuel_price_{$sensor->id}", $stat);
            }
        }
    }
}