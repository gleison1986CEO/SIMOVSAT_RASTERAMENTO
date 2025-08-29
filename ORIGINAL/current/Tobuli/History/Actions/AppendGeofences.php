<?php

namespace Tobuli\History\Actions;

use Formatter;
use Tobuli\History\Stats\StatSum;

class AppendGeofences extends ActionAppend
{
    public function boot()
    {
    }

    public function proccess(&$position)
    {
        $position->geofences = $this->history->inGeofences($position);
    }
}