<?php

namespace Tobuli\Importers\Device;

use Tobuli\Importers\ImportManager;

class DeviceImportManager extends ImportManager
{
    protected function getReadersList(): array
    {
        return [
            'csv' => Readers\DeviceCsvReader::class,
        ];
    }

    public function getImporterClass(): string
    {
        return DeviceImporter::class;
    }
}