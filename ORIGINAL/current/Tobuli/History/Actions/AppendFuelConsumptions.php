<?php

namespace Tobuli\History\Actions;

class AppendFuelConsumptions extends ActionAppend
{
    static public function required()
    {
        return [
            AppendFuelConsumptionGPS::class,
            AppendFuelConsumptionSensors::class,
            AppendFuelConsumptionLevelSensors::class
        ];
    }

    public function boot() {}

    public function proccess(& $position) {}
}