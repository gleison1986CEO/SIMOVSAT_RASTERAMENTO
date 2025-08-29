<?php

namespace Tobuli\Reports\Reports;

use CustomFacades\Repositories\EventRepo;
use Formatter;
use Carbon\Carbon;
use Tobuli\Entities\Event;
use Tobuli\Reports\DeviceReport;

class EventDeviceReport extends DeviceReport
{
    protected $offline_timeout;

    const TYPE_ID = 8;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.events');
    }

    protected function beforeGenerate()
    {
        if (!$this->getSkipBlankResults())
            return;

        $query = $this->getDevicesQuery()->whereHas('events', function($q){
            $q->whereBetween('time', [$this->date_from, $this->date_to]);
            $q->where('user_id', $this->user->id);

            if ($types = array_get($this->parameters, 'event_types'))
                $q->whereIn('type', $types);
        });

        $this->setDevicesQuery($query);
    }

    protected function generateDevice($device)
    {
        $query = Event::with(['geofence'])
            ->whereBetween('time', [$this->date_from, $this->date_to])
            ->where('user_id', $this->user->id)
            ->where('device_id', $device->id)
            ->orderBy('time', 'asc');

        if ($types = array_get($this->parameters, 'event_types'))
            $query->whereIn('type', $types);

        $events = $query->get();

        if (empty($events))
            return null;

        $totals = [];

        foreach ($events as & $event) {
            $event['time']     = Formatter::time()->human($event['time']);
            $event['location'] = $this->getLocation((object)[
                'latitude' => $event['latitude'],
                'longitude' => $event['longitude']
            ]);

            if (empty($totals[$event['message']]))
                $totals[$event['message']] = [
                    'title' => trans('front.total') . ' ' . $event['message'],
                    'value' => 0,
                ];

            $totals[$event['message']]['value']++;
        }

        return [
            'meta' => $this->getDeviceMeta($device),
            'table' => [
                'rows' => $events
            ],
            'totals' => $totals
        ];
    }

    protected function toCSVData($file)
    {
        foreach ($this->getItems() as $item) {
            $metas = array_pluck($item['meta'], 'value');

            if (empty($item['table']['rows']))
                continue;

            foreach ($item['table']['rows'] as $row) {
                $values = $metas;
                $values[] = $row['time'];
                $values[] = $row['message'];
                $values[] = strip_tags($row['location']);

                fputcsv($file, $values);
            }
        }
    }
}