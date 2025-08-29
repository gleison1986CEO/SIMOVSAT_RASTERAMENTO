<?php

namespace Tobuli\Importers\Readers;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\File;
use Tobuli\Importers\RemapInterface;
use Tobuli\Importers\RemapTrait;
use Tobuli\Importers\XlsCollectionImporter;

abstract class ExcelReader extends Reader implements RemapInterface
{
    use RemapTrait;

    private $readerType;

    public function __construct($readerType)
    {
        $this->readerType = $readerType;
    }

    public function getHeaders(File $file): array
    {
        $sheets = Excel::toCollection(new XlsCollectionImporter(), $file, null, $this->readerType);
        $rows = $sheets->first();
        return $rows->shift()->toArray();
    }

    public function read($file)
    {
        $sheets = Excel::toCollection(new XlsCollectionImporter(), $file, null, $this->readerType);
        $rows = $sheets->first();
        $headerRow = $rows->shift()
            ->toArray();
        $rows = $rows->toArray();

        $this->remapHeaders($headerRow);

        foreach ($rows as $key => $row) {
            $rows[$key] = $this->parseRow($row, $headerRow);
        }

        if (! is_array($headerRow)) {
            return null;
        }

        if (empty($headerRow)) {
            return null;
        }

        if (empty($rows)) {
            return null;
        }

        return $rows;
    }

    public function supportsFile(File $file): bool
    {
        try {
            Excel::toCollection(new XlsCollectionImporter(), $file, null, $this->readerType);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function isValidFormat($file)
    {
        try {
            $sheets = Excel::toCollection(new XlsCollectionImporter(), $file, null, $this->readerType);
            $rows = $sheets->first();
            $headerRow = $rows->shift()
                ->toArray();
            $rows = $rows->toArray();

            $this->remapHeaders($headerRow);
        } catch(\PHPExcel_Reader_Exception $e) {
            return false;
        }

        if (empty($rows)) {
            return false;
        }

        if (! array_filter($headerRow)) {
            return false;
        }

        if (! isset($this->requiredFieldRules)) {
            return true;
        }

        return $this->validateRequiredFields(array_flip($headerRow));
    }

    protected function parseRow($row, $headers = [])
    {
        return empty($headers) ? $row : array_combine($headers, $row);
    }

    protected function validateRequiredFields($fieldNames)
    {
        $validator = Validator::make($fieldNames, $this->requiredFieldRules);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }
}
