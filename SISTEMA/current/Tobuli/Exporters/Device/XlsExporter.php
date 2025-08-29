<?php

namespace Tobuli\Exporters\Device;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Tobuli\Exporters\XlsCollectionExporter;

class XlsExporter extends Exporter
{
    private $data;

    public function export($devices, $attributes)
    {
        $this->attributes = $attributes;
        $this->filename = uniqid('', TRUE) . '.xls';

        $this->data = [];

        if (is_array($devices) || $devices instanceof Collection) {
            $this->exportCollection($devices);
        } else {
            $this->exportQuery($devices);
        }

        return $this;
    }

    public function download()
    {
        $export = new XlsCollectionExporter($this->data, $this->attributes);
        $export->setColumnFormat([
            'imei' => NumberFormat::FORMAT_TEXT,
        ]);

        return Excel::download($export, $this->filename, \Maatwebsite\Excel\Excel::XLS);
    }

    protected function writeRow($device)
    {
        $this->data[] = $device->only($this->attributes);
    }
}
