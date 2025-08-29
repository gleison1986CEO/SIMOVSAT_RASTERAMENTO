<?php

namespace Tobuli\History\Actions;


class AppendOverspeedGeofenceOnly extends ActionAppend
{
    static public function required()
    {
        return [
            AppendOverspeedStatic::class,
            AppendGeofences::class,
        ];
    }

    public function boot() {}

    public function proccess(&$position)
    {
        if(empty($position->geofences))
            $position->overspeeding = 0;
    }
}