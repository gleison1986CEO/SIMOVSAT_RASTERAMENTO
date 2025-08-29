<?php

namespace Tobuli\Helpers\Formatter\Unit;

use CustomFacades\Language;

class Duration extends Numeric
{
    public function byMeasure($unit) {}

    public function human($seconds)
    {
        $seconds = intval($seconds);

        // extract hours
        $hours = floor($seconds / (60 * 60));

        // extract minutes
        $divisor_for_minutes = $seconds % (60 * 60);
        $minutes = floor($divisor_for_minutes / 60);

        // extract the remaining seconds
        $divisor_for_seconds = $divisor_for_minutes % 60;
        $seconds = ceil($divisor_for_seconds);

        //if (Language::dir() == 'rtl')
        //    return $this->rtl($seconds, $minutes, $hours);

        return $this->ltr($seconds, $minutes, $hours);
    }

    private function ltr($seconds, $minutes, $hours)
    {
        if ($hours < 0 || $minutes < 0 || $seconds < 0)
            return '0' . trans('front.second_short');

        $result = $seconds . trans('front.second_short');

        if ($minutes)
            $result = $minutes . trans('front.minute_short') . ' ' . $result;

        if ($hours)
            $result = $hours . trans('front.hour_short') . ' ' . $result;

        return $result;
    }

    private function rtl($seconds, $minutes, $hours)
    {
        if ($hours < 0 || $minutes < 0 || $seconds < 0)
            return trans('front.second_short') . '0';

        $result = trans('front.second_short') . $seconds;

        if ($minutes)
            $result =  $result . ' ' . trans('front.minute_short') . $minutes;

        if ($hours)
            $result =   $result . ' ' . trans('front.hour_short') . $hours;

        return $result;
    }
}