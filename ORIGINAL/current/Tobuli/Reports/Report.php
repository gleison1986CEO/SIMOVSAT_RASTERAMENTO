<?php


namespace Tobuli\Reports;

use Cache;
use Formatter;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Bugsnag\BugsnagLaravel\BugsnagFacade as Bugsnag;
use Tobuli\Entities\Poi;

abstract class Report
{
    protected $formats = ['html', 'xls', 'pdf', 'pdf_land'];
    protected $enableFields = ['geofences', 'speed_limit', 'stops', 'show_addresses', 'zones_instead', 'devices'];
    protected $disableFields = [];

    protected $user;

    protected $format;

    protected $date_from;

    protected $date_to;

    protected $items;
    protected $totals;

    protected $metas;
    protected $devices;
    protected $geofences;
    protected $pois;
    protected $parameters;
    protected $stop_seconds = 60;
    protected $speed_limit = null;
    protected $skip_blank_results = false;
    public $zones_instead = false;
    public $show_addresses = false;

    abstract protected function generate();
    abstract public function typeID();
    abstract public function title();

    public function __construct()
    {
        $this->setMetaList($this->defaultMetas());
    }

    private function _generate()
    {
        $this->beforeGenerate();
        $this->generate();
        $this->afterGenerate();
    }

    public static function isEnabled() {return true;}

    protected function beforeGenerate()
    {
        if ($this->zones_instead && (empty($this->geofences) || $this->geofences->isEmpty()))
            $this->geofences = $this->user->geofences;
    }

    protected function afterGenerate() {}

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setFormat($format)
    {
        $this->format = strtolower($format);
    }

    /**
     * @param Geofence[]
     */
    public function setGeofences($geofences)
    {
        $this->geofences = $geofences;
    }

    public function getGeofences()
    {
        return $this->geofences;
    }

    public function getGeofenceNames()
    {
        return $this->getGeofences()->pluck('name')->all();
    }

    /**
     * @param Poi[]
     */
    public function setPois($pois)
    {
        $this->pois = $pois;
    }

    public function getPois()
    {
        return $this->pois;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function setStopMinutes($minutes)
    {
        $this->stop_seconds = $minutes * 60;
    }

    public function setStopSeconds($seconds)
    {
        $this->stop_seconds = $seconds;
    }

    public function setSpeedLimit($speed)
    {
        $this->speed_limit = $speed;
    }

    public function getSpeedLimit()
    {
       return $this->speed_limit;
    }

    public function setZonesInstead($value)
    {
        $this->zones_instead = $value;
    }

    public function setShowAddresses($value)
    {
        $this->show_addresses = $value;
    }

    public function setSkipBlankResults($value)
    {
        $this->skip_blank_results = $value;
    }

    public function getSkipBlankResults()
    {
        return $this->skip_blank_results;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getDateFrom()
    {
        return $this->date_from;
    }

    public function getDateTo()
    {
        return $this->date_to;
    }

    public function getItems()
    {
        if (empty($this->items))
            return [];

        return $this->items;
    }

    public function globalTotals($key = null)
    {
        if (is_null($key))
            return $this->totals;

        if (empty($this->totals[$key]))
            return null;

        return $this->totals[$key];
    }

    public function getType()
    {
        return [
            'type'    => $this::typeID(),
            'name'    => $this::title(),
            'formats' => $this->formats,
            'fields'  => $this::enabledFields(),
            'parameters' => []
        ];
    }

    public function setRange($date_from, $date_to)
    {
        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    public function getFilename()
    {
        $items = [
            //$this::title(),
            snake_case(class_basename(get_class($this))),
            Formatter::time()->convert($this->date_from),
            Formatter::time()->convert($this->date_to),
            time()
        ];

        $filename = implode('_', $items);
        $filename = mb_convert_encoding($filename, 'ASCII');
        $filename = strtr($filename, [
            ' ' => '_',
            '-' => '_',
            ':' => '_',
            '/' => '_'
        ]);

        return $filename;
    }

    public function getView()
    {
        return 'front::Reports.partials.type_' . $this::TYPE_ID;
    }

    public function toHTML()
    {
        return view($this->getView())->with(['report' => $this]);
    }

    public function toPDF()
    {
        return PDF::loadView($this->getView(), ['report' => $this]);
    }

    public function toPDFLand()
    {
        return $this->toPDF()->setPaper('A4', 'landscape');
    }

    public function toCSV()
    {
        $filename = $this->getFilename() . '.csv';
        $filePath = storage_path('cache/' . $filename);

        $file = fopen($filePath, 'wb');

        // UTF-8 BOM
        fwrite($file,"\xEF\xBB\xBF");

        $this->toCSVData($file);

        fclose($file);

        return $filePath;
    }

    protected function toCSVData($file) {
        return;
    }

    public function save()
    {
        $this->_generate();

        $path = storage_path('cache');
        $filename = $this->getFilename();
        $filePath = $path . '/' . $filename;

        switch ($this->format) {
            case 'html':
                file_put_contents($filePath . '.html', $this->toHTML()->render());

                return $filePath . '.html';
            case 'pdf':
                $this->toPDF()->save($filePath . '.pdf');

                return $filePath . '.pdf';

            case 'pdf_land':
                $this->toPDFLand()->save($filePath . '.pdf');

                return $filePath . '.pdf';

            case 'xls':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);
                Excel::store($export, $filename.'.xls', 'storage_cache', \Maatwebsite\Excel\Excel::XLS);

                return $filePath . '.xls';

            case 'csv':
                return $this->toCSV();

            default:
                throw new \Exception("Wrong report format '{$this->format}'");
        }
    }

    public function download()
    {
        $this->_generate();

        switch ($this->format) {
            case 'html':
                header('Content-disposition: attachment; filename="' . utf8_encode($this->getFilename()) . '.html"');
                header('Content-type: text/html');

                echo $this->toHTML()->render();
                break;

            case 'pdf':
                return $this->toPDF()->download($this->getFilename() . '.pdf');

            case 'pdf_land':
                return $this->toPDFLand()->download($this->getFilename() . '.pdf');

            case 'xls':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);

                return Excel::download($export, $this->getFilename().'.xls', \Maatwebsite\Excel\Excel::XLS);

            case 'csv':
                $headers = array();

                return response()->download($this->toCSV(), utf8_encode($this->getFilename()) . '.csv', $headers);
        }
    }

    public function view()
    {
        $this->_generate();

        switch ($this->format) {
            case 'html':
                return $this->toHTML();
                break;

            case 'pdf':
                return $this->toPDF();
                break;

            case 'pdf_land':
                return $this->toPDFLand();
                break;

            case 'xls':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);

                return Excel::raw($export, \Maatwebsite\Excel\Excel::XLS);
                break;
        }
    }

    protected function getLocation($position, $text = null)
    {
        if (empty($position->latitude) && empty($position->longitude))
            return $text;

        if (is_null($text))
            $text = $this->getAddress($position);

        return googleMapLink($position->latitude, $position->longitude, $text);
    }

    protected function getAddress($position)
    {
        $address = null;

        if (empty($position->latitude) && empty($position->longitude))
            return $address;

        if ($this->zones_instead)
            $address = $this->getGeofencesNames($position);

        if ($this->show_addresses && ! $address )
            $address = getGeoAddress($position->latitude, $position->longitude);

        return $address ? htmlentities(removeEmoji($address)) : null;
    }

    protected function getGeofencesIn($position)
    {
        if (empty($position))
            return [];

        if (empty($this->geofences))
            return [];

        return $this->geofences->filter(function($geofence) use ($position) {
            return $geofence->pointIn($position);
        });
    }

    protected function getGeofencesNames($position)
    {
        $geofences = $this->getGeofencesIn($position);

        if ( ! $geofences)
            return null;

        return $geofences->implode('name', ', ');
    }

    /**
     * @return array
     */
    abstract protected function defaultMetas();

    public function setMetas($data)
    {
        if (empty($data))
            return;

        $list = ReportManager::getMetaList($this->user);

        $list = array_filter($list, function($title, $key) use ($data){
            return in_array($key, $data);
        }, ARRAY_FILTER_USE_BOTH);

        $this->setMetaList($list);
    }

    private function setMetaList($list)
    {
        if (empty($list))
            return;

        foreach ($list as $key => $title) {
            list($model, $attribute) = explode('.', $key, 2);

            $this->metas[$model][$key] = [
                'title' => $title,
                'attribute' => $attribute,
            ];
        }
    }

    public function metas($object = null)
    {
        if (empty($this->metas))
            return [];

        if (is_null($object)) {
            return array_collapse($this->metas);
        }

        if (empty($this->metas[$object]))
            return [];

        return $this->metas[$object];
    }
}