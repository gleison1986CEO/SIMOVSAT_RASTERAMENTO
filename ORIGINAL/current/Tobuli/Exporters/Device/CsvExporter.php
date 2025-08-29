<?php

namespace Tobuli\Exporters\Device;


use Illuminate\Database\Eloquent\Collection;

class CsvExporter extends Exporter
{
    /**
     * @var resource
     */
    protected $pointer;

    public function export($devices, $attributes)
    {
        $this->attributes = $attributes;
        $this->filename   = uniqid('', TRUE) . '.csv';
        $this->file       = storage_path($this->filename);
        $this->pointer    = fopen($this->file, 'wb');

        // UTF-8 BOM
        fwrite($this->pointer,"\xEF\xBB\xBF");

        fputcsv($this->pointer, $this->attributes);

        if (is_array($devices) || $devices instanceof Collection) {
            $this->exportCollection($devices);
        } else {
            $this->exportQuery($devices);
        }

        fclose($this->pointer);

        return $this;
    }

    protected function writeRow($device)
    {
        $values = [];

        foreach ($this->attributes as $attribute) {
            $values[] = $device->$attribute;
        }

        fputcsv($this->pointer, $values);
    }
}