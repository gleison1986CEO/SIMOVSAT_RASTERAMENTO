<?php


namespace Tobuli\Exporters\Device;


class ExporterManager
{
    private $data;
    private $attributes;

    public function __construct($data, $attributes)
    {
        $this->data = $data;
        $this->attributes = $attributes;

        set_time_limit(600);
    }

    public function download($format)
    {
        $exporter = $this->loadExporter($format);

        return $exporter->export($this->data, $this->attributes)->download();
    }

    private function loadExporter($format)
    {
        $exporter_class = 'Tobuli\Exporters\Device\\' . ucfirst($format) . 'Exporter';

        if ( ! class_exists($exporter_class, true)) {
            throw new \Exception('Format exporter class not found!');
        }

        return new $exporter_class();
    }
}