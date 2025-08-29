<?php

namespace Tobuli\Helpers\Formatter;

use Illuminate\Support\Facades\Session;
use Tobuli\Entities\Timezone;
use Tobuli\Entities\User;
use Language;

class Formatter
{
    public $speed;
    public $distance;
    public $altitude;
    public $capacity;
    public $duration;
    public $course;
    public $fuelAvg;
    public $weight;

    public function __construct()
    {
        $this->speed    = new Unit\Speed();
        $this->distance = new Unit\Distance();
        $this->altitude = new Unit\Altitude();
        $this->capacity = new Unit\Capacity();
        $this->duration = new Unit\Duration();
        $this->time     = new Unit\Time();
        $this->course   = new Unit\Course();
        $this->fuelAvg  = new Unit\FuelAvg();
        $this->weight   = new Unit\Weight();

        $this->byDefault();
    }

    public function byDefault()
    {
        $defaults = settings('main_settings');

        $this->speed->setMeasure( $defaults['default_unit_of_distance'] ?? 'km' );
        $this->distance->setMeasure( $defaults['default_unit_of_distance'] ?? 'km' );
        $this->altitude->setMeasure( $defaults['default_unit_of_altitude'] ?? 'mt' );
        $this->capacity->setMeasure( $defaults['default_unit_of_capacity'] ?? 'lt' );
        $this->fuelAvg->setPer($defaults['default_fuel_avg_per'] ?? 'distance');

        if ($timezone = Timezone::find($defaults['default_timezone'] ?? null)) {
            $this->time->byTimezone($timezone);
        }
    }

    public function byUser(User $user)
    {
        $lang = Session::has('language') ? Session::get('language') : $user->lang;
        Language::set($lang);

        $this->speed->setMeasure($user->unit_of_distance);
        $this->distance->setMeasure($user->unit_of_distance);
        $this->altitude->setMeasure($user->unit_of_altitude);
        $this->capacity->setMeasure($user->unit_of_capacity);

        $this->time->byTimezone($user->userTimezone, $user->getUserDSTRange());
    }

    public function speed($value = null)
    {
        if (is_null($value)) {
            return $this->speed;
        }

        return $this->speed->human($value);
    }

    public function distance($value = null)
    {
        if (is_null($value)) {
            return $this->distance;
        }

        return $this->distance->human($value);
    }

    public function altitude($value = null)
    {
        if (is_null($value)) {
            return $this->altitude;
        }

        return $this->altitude->human($value);
    }

    public function capacity($value = null)
    {
        if (is_null($value)) {
            return $this->capacity;
        }

        return $this->capacity->human($value);
    }

    public function duration($value = null)
    {
        if (is_null($value)) {
            return $this->duration;
        }

        return $this->duration->human($value);
    }

    public function time($value = null)
    {
        if (is_null($value)) {
            return $this->time;
        }

        return $this->time->human($value);
    }

    public function course($value = null)
    {
        if (is_null($value)) {
            return $this->course;
        }

        return $this->course->human($value);
    }

    public function fuelAvg($value = null)
    {
        if (is_null($value)) {
            return $this->fuelAvg;
        }

        return $this->fuelAvg->human($value);
    }

    public function weight($value = null)
    {
        if (is_null($value)) {
            return $this->weight;
        }

        return $this->weight->human($value);
    }
}
