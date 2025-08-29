<?php

namespace Tobuli\History\Actions;


class AppendUnderspeed extends ActionAppend
{
    protected $speed_limit;

    public function boot()
    {
        $this->speed_limit = $this->history->config('speed_limit');
    }

    public function proccess(&$position)
    {
        $position->underspeeding = 0;

        if ( ! $this->isUnderspeed($position))
            return;

        $position->underspeeding++;

        if ($previous = $this->getPrevPosition())
            $position->underspeeding += $previous->underspeeding;
    }

    protected function isUnderspeed($position)
    {
        return ! is_null($this->speed_limit) && $this->speed_limit > $position->speed;
    }
}