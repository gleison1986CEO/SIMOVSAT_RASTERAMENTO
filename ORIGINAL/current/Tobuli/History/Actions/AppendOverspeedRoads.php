<?php

namespace Tobuli\History\Actions;


class AppendOverspeedRoads extends ActionAppend
{
    protected $tolerance;

    static public function required()
    {
        return [
            AppendSpeedLimit::class,
        ];
    }

    public function boot()
    {
        $this->tolerance = intval($this->history->config('speed_limit_tolerance'));
    }

    public function proccess(&$position)
    {
        $position->overspeeding = 0;

        if ( ! $this->isOverspeed($position))
            return;

        $position->overspeeding++;

        $previous = $this->getPrevPosition();

        if ($previous && !empty($previous->overspeeding) && $previous->speed_limit == $position->speed_limit)
            $position->overspeeding += $previous->overspeeding;
    }

    protected function isOverspeed($position)
    {
        return !is_null($position->speed_limit) && ($position->speed_limit + $this->tolerance) < $position->speed;
    }
}