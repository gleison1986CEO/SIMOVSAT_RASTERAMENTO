<?php namespace Tobuli\Reports\Reports;

use Formatter;
use Tobuli\Entities\Poi;
use Tobuli\History\Actions\GroupStop;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\EngineHours;
use Tobuli\History\Actions\GeofencesIn;
use Tobuli\Reports\DeviceHistoryReport;

class PoiStopDurationReport extends DeviceHistoryReport
{
    const TYPE_ID = 54;

    protected $stop_duration;

    protected $distance_tolerance;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.poi_stop_duration');
    }

    protected function beforeGenerate()
    {
        parent::beforeGenerate();

        $this->stop_duration = $this->parameters['stop_duration'] * 60;
        $this->distance_tolerance = $this->parameters['distance_tolerance'] / 1000;
    }

    protected function getActionsList()
    {
        return [
            DriveStop::class,
            Duration::class,
            EngineHours::class,

            GroupStop::class,
        ];
    }

    protected function getTable($data)
    {
        $rows = [];

        foreach ($data['groups']->all() as $group)
        {
            $duration = $group->stats()->get('duration')->value();

            if ($duration < $this->stop_duration)
                continue;

            $poi = $this->getPoiIn($group->getStartPosition());

            if ( ! $poi)
                continue;

            $distance = $poi->pointDistance($group->getStartPosition());

            $rows[] = $this->getDataFromGroup($group, [
                'start_at',
                'end_at',
                'duration',
                'engine_idle',
                'location',
            ]) + [
                'near' => Formatter::distance()->human($distance) . ' - ' . $poi->name
            ];
        }

        return [
            'rows'   => $rows,
            'totals' => [],
        ];
    }

    /**
     * @param $position
     * @return Poi|null
     */
    protected function getPoiIn($position)
    {
        foreach ($this->pois as $poi) {
            if ( ! $poi->pointIn($position, $this->distance_tolerance))
                continue;

            return $poi;
        }

        return null;
    }
}