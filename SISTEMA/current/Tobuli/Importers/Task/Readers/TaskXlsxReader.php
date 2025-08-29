<?php

namespace Tobuli\Importers\Task\Readers;

use Maatwebsite\Excel\Excel;
use Tobuli\Importers\Readers\ExcelReader;

class TaskXlsxReader extends ExcelReader
{
    public function __construct()
    {
        parent::__construct(Excel::XLSX);
    }
}
