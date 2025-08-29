<?php

namespace Tobuli\Importers\POI\Readers;

use Tobuli\Importers\Readers\CsvReader;

class POICsvReader extends CsvReader
{
    protected function parseRow($row, $headers = [])
    {
        $row = parent::parseRow($row, $headers);

        if (array_key_exists('coordinates', $row))
            $row['coordinates'] = $this->parseCoordinates($row['coordinates']);

        return $row;
    }

    protected function parseCoordinates($data)
    {
        $coordinates = [];

        $coords = trim(preg_replace('/\r\n|\r|\n|\t+/', '', $data));

        if ($coords) {
            $coords = explode(',', $coords);

            if (isset($coords[0]) && isset($coords[1])) {
                $coordinates = [
                    'lat' => trim($coords[0]),
                    'lng' => trim($coords[1]),
                ];
            }
        }

        return $coordinates;
    }
}
