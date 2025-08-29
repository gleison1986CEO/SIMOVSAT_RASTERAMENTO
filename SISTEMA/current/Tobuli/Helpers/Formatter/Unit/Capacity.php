<?php

namespace Tobuli\Helpers\Formatter\Unit;

class Capacity extends Numeric
{
    protected $precision = 2;

    public function __construct()
    {
        $this->setMeasure('lt');
    }

    public function byMeasure($unit)
    {
        switch (strtolower($unit)) {
            case 'l':
            case 'lt':
                $this->setRatio(1);
                $this->setUnit(trans('front.liters'));
                break;

            case 'gl':
                $this->setRatio(0.264172053);
                $this->setUnit(trans('front.gallons'));
                break;

            default:
                $this->setRatio(1);
                $this->setUnit($unit);
        }
    }
}