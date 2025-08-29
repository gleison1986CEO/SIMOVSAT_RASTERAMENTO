<?php

namespace Tobuli\Exporters;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class XlsCollectionExporter extends XlsExporter implements FromCollection, WithHeadings, WithColumnFormatting
{
    private $data;
    private $headings;
    private $columnFormats;

    public function __construct($data, $headings)
    {
        if (is_array($data)) {
            $data = collect($data);
        }

        $this->data = $data;

        $this->headings = $headings;
        $this->columnFormats = [];
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function setColumnFormat(array $formats)
    {
        foreach ($formats as $attribute => $format) {
            $index = array_search($attribute, $this->headings);

            if ($index === false) {
                continue;
            }

            $index++; //excel letter conversion starts from 1

            $columnLetter = Coordinate::stringFromColumnIndex($index);
            $this->columnFormats[$columnLetter] = $format;
        }
    }

    public function columnFormats(): array
    {
        return $this->columnFormats;
    }
}
