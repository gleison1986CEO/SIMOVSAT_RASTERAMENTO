<?php


namespace Tobuli\Exporters\Device;


abstract class Exporter
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var array
     */
    protected $attributes = [];

    public function download()
    {
        return response()
            ->download($this->file, $this->filename)
            ->deleteFileAfterSend(true);
    }

    protected function exportCollection($devices)
    {
        foreach ($devices as $device) {
            $this->writeRow($device);
        }
    }

    protected function exportQuery($query)
    {
        foreach ($this->attributes as $attribute) {
            if (in_array($attribute, ['protocol', 'latitude', 'longitude', 'altitude', 'course', 'speed', 'last_connect_time', 'stop_duration']))
                $query->with('traccar');

            if (in_array($attribute, ['users_emails']))
                $query->with('users');
        }

        $query->chunk(1000, function($devices) {
            $this->exportCollection($devices);
        });
    }
}