<?php

namespace Tobuli\Helpers\Formatter\Unit;

use CustomFacades\Appearance;
use Tobuli\Entities\Timezone;

class Time extends Unit
{
    protected $datetime_format;
    protected $date_format;
    protected $time_format;

    protected $timezone;
    protected $DST;

    protected $yearCache = [];

    public function __construct()
    {
        $this->datetime_format = Appearance::getSetting('default_date_format').' '.Appearance::getSetting('default_time_format');
        $this->date_format = Appearance::getSetting('default_date_format');
        $this->time_format = Appearance::getSetting('default_time_format');
    }

    public function byTimezone(Timezone $timezone, $DST = null)
    {
        $this->timezone = $timezone;
        $this->DST = $DST;
    }

    public function unit()
    {
        $zone = $this->getZone(time());

        return 'UTC ' . str_replace(['hours', '-minutes', 'minutes'], '', $zone);
    }

    public function convert($value, $format = 'Y-m-d H:i:s')
    {
        return date($format, $this->applyDST($value));
    }

    public function reverse($value, $format = 'Y-m-d H:i:s')
    {
        return date($format, $this->applyReverseDST($value));
    }

    public function format($value, $format = null)
    {
        if (! isset($format)) {
            $format = $this->datetime_format;
        }

        return date($format, strtotime($value));
    }

    public function human($value)
    {
        if (empty($value) || substr($value, 0, 4) == '0000') {
            return '-';
            //return trans('front.invalid_date');
        }

        return $this->convert($value, $this->datetime_format);
    }

    public function formatDate($value)
    {
        return $this->format($value, $this->date_format);
    }

    public function formatTime($value)
    {
        return $this->format($value, $this->time_format);
    }

    public function date($value)
    {
        return $this->convert($value, $this->date_format);
    }

    public function time($value)
    {
        return $this->convert($value, $this->time_format);
    }

    public function now()
    {
        return $this->timestamp(date('Y-m-d H:i:s'));
    }

    public function timestamp($value)
    {
        return strtotime($this->convert($value));
    }

    private function applyDST($value)
    {
        return $this->calculateDST($value, false);
    }

    private function applyReverseDST($value)
    {
        return $this->calculateDST($value, true);
    }

    private function calculateDST($value, $reverse = false)
    {
        $time = strtotime($value);

        $zone = $this->getZone($time, $reverse);

        return strtotime($zone, $time);
    }

    private function getZone($time, $reverse = false)
    {
        $zone = $reverse
            ? $this->timezone->reversedZone
            : $this->timezone->zone;

        $range = $this->getDSTRange($time);

        if (! $range) {
            return $zone;
        }

        if ($time > $range['from'] && $time < $range['to']) {
            $zone = $reverse
                ? $this->timezone->reversedDSTZone
                : $this->timezone->DSTZone;
        }

        return $zone;
    }

    private function getDSTRange($time)
    {
        if (! $this->DST) {
            return null;
        }

        $year = date('Y', $time);

        if (isset($this->yearCache[$year])) {
            return $this->yearCache[$year];
        }

        $DST = calculateDSTRange($this->DST, $time);

        $fromDate = strtotime($year.'-'.$DST->date_from);
        $toDate = strtotime($year.'-'.$DST->date_to);

        if ($fromDate > $toDate) {
            $toDate = strtotime('+1 year', $toDate);
        }

        return $this->yearCache[$year] = [
            'from' => $fromDate,
            'to' => $toDate,
        ];
    }
}
