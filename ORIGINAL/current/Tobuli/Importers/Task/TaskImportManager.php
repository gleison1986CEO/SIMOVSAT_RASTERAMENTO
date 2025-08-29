<?php

namespace Tobuli\Importers\Task;

use Tobuli\Importers\ImportManager;

class TaskImportManager extends ImportManager
{
    protected function getReadersList(): array
    {
        return [
            'csv' => Readers\TaskCsvReader::class,
            'xls' => Readers\TaskXlsReader::class,
            'xlsx' => Readers\TaskXlsxReader::class,
        ];
    }

    public function getImporterClass(): string
    {
        return TaskImporter::class;
    }
}
