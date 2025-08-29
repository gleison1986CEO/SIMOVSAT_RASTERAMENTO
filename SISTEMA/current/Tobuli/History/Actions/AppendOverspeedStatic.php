<?php

namespace Tobuli\History\Actions;


class AppendOverspeedStatic extends ActionAppend
{
    protected $speed_limit;

    public function boot()
    {
        $this->speed_limit = $this->history->config('speed_limit');
    }

    public function proccess(&$position)
    {
        $position->overspeeding = 0;
        $position->speed_limit = $this->speed_limit;

        if ( ! $this->isOverspeed($position))
            return;

        $position->overspeeding++;

        $previous = $this->getPrevPosition();

        if ($previous && isset($previous->overspeeding))
            $position->overspeeding += $previous->overspeeding;
    }

    protected function isOverspeed($position)
    {
        return ! is_null($position->speed_limit) && $position->speed_limit < $position->speed;
    }
}