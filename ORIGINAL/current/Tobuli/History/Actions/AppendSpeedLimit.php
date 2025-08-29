<?php

namespace Tobuli\History\Actions;


use Tobuli\Services\SpeedLimitService;

class AppendSpeedLimit extends ActionAppend
{
    protected $min_speed;

    protected $limits = [];

    protected $service;

    public function boot()
    {
        $this->min_speed = 10;

        $this->service = new SpeedLimitService();
    }

    public function proccess(&$position)
    {
        $position->speed_limit = $this->getSpeedLimit($position);
    }

    public function preproccess($positions)
    {
        $this->limits = [];

        $suitables = $positions->filter(function($position) {
            return $position->speed > $this->min_speed;
        });

        if ($suitables->isEmpty())
            return;

        try {
            $this->limits = $this->service->get($suitables);
        } catch (\Exception $e) {
            $this->limits = [];
        }
    }

    protected function getSpeedLimit($position)
    {
        return array_get($this->limits, $this->service->getKey($position));
    }
}