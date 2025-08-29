<?php

namespace Tobuli\Reports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Tobuli\Exporters\XlsExporter;

class ReportXlsViewExport extends XlsExporter implements FromView
{
    private $view;
    private $data;

    public function __construct($view, $data)
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function view(): View
    {
        return view($this->view, $this->data);
    }
}
