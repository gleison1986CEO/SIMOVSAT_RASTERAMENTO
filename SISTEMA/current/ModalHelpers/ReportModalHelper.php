<?php namespace ModalHelpers;

set_time_limit(18000);

use Formatter;
use Carbon\Carbon;
use CustomFacades\Server;
use Tobuli\Entities\Event;
use Tobuli\Helpers\ReportHelper;
use Tobuli\Reports\ReportManager;
use Tobuli\Entities\DeviceExpense;
use Tobuli\Entities\DeviceExpensesType;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Reports\Reports\GeofencesStopReport;
use Tobuli\Reports\Reports\GeofencesStopShiftReport;
use Tobuli\Reports\Reports\RoutesSummarizedReport;
use Tobuli\Reports\Reports\SentCommandsReport;
use CustomFacades\Repositories\PoiRepo;
use CustomFacades\Repositories\ReportRepo;
use CustomFacades\Repositories\GeofenceRepo;
use CustomFacades\Validators\ReportFormValidator;
use CustomFacades\Validators\ReportSaveFormValidator;
use Illuminate\Support\Facades\Validator;

class ReportModalHelper extends ModalHelper
{
    private $types = [];

    private $reportManager;

    function __construct()
    {
        parent::__construct();

        $this->reportManager = new ReportManager();

        Server::setMemoryLimit(config('server.report_memory_limit'));
    }

    public function get()
    {
        $this->checkException('reports', 'view');

        $reports = ReportRepo::searchAndPaginate(['filter' => ['user_id' => $this->user->id]], 'id', 'desc', 10);
        $types = $this->reportManager->getList();

        if ($this->api) {
            $reports = $reports->toArray();
            $reports['url'] = route('api.get_reports');
            foreach ($reports['data'] as &$item) {
                $item['devices'] = array_pluck($item['devices'], 'id');
                $item['geofences'] = array_pluck($item['geofences'], 'id');
            }
            $new_arr = [];
            foreach ($types as $id => $title) {
                array_push($new_arr, ['id' => $id, 'title' => $title]);
            }
            $types = $new_arr;
        }

        return compact('reports', 'types');
    }

    public function createData()
    {
        $this->checkException('reports', 'create');

        $devices = $this->user->devices()->unexpired()->get();

        if (empty($devices))
            return $this->api ? ['status' => 0, 'errors' => ['id' => trans('front.no_devices')]] : modal(trans('front.no_devices'), 'alert');

        $geofences = GeofenceRepo::getWhere(['user_id' => $this->user->id]);

        $formats = [
            'html' => trans('front.html'),
            'xls' => trans('front.xls'),
            'pdf' => trans('front.pdf'),
            'pdf_land' => trans('front.pdf_land'),
            //'csv' => trans('front.csv'),
        ];

        $stops = config('tobuli.stops_seconds');

        $filters = [
            '0' => '',
            '1' => trans('front.today'),
            '2' => trans('front.yesterday'),
            '3' => trans('front.before_2_days'),
            '4' => trans('front.before_3_days'),
            '5' => trans('front.this_week'),
            '6' => trans('front.last_week'),
            '7' => trans('front.this_month'),
            '8' => trans('front.last_month'),
        ];

        $metas = $this->reportManager->getMetaList($this->user);

        $types = $this->reportManager->getList();
        $types_list = $this->reportManager->getList();

        if ($this->api) {
            $formats = apiArray($formats);
            $stops = apiArray($stops);
            $filters = apiArray($filters);
            $types = apiArray($types);
            $metas = apiArray($metas);
        }

        $reports = ReportRepo::searchAndPaginate(['filter' => ['user_id' => $this->user->id]], 'id', 'desc', 10);
        $reports->setPath(route('reports.index'));

        if ($this->api) {
            $reports = $reports->toArray();
            $reports['url'] = route('api.get_reports');
            $geofences = $geofences->toArray();

            //devices list return as array, not object
            $devices = array_values( $devices->all() );
        } else {
            $devices = groupDevices($devices, $this->user);
        }

        return compact('devices', 'geofences', 'formats', 'stops', 'filters', 'types', 'types_list', 'reports', 'metas');
    }

    public function create()
    {
        if (empty($this->data['id']))
            $this->checkException('reports', 'store');
        else
            $this->checkException('reports', 'update', ReportRepo::find($this->data['id']));

        if ($this->api) {
            if (isset($this->data['devices']) && !is_array($this->data['devices']))
                $this->data['devices'] = json_decode($this->data['devices'], TRUE);

            if (isset($this->data['geofences']) && !is_array($this->data['geofences']))
                $this->data['geofences'] = json_decode($this->data['geofences'], TRUE);
        }

        $this->validate($this->data);

        ReportSaveFormValidator::validate('create', $this->data);

        $now = Carbon::parse( Formatter::time()->convert(date('Y-m-d H:i:s'), 'Y-m-d') );
        $days = $now->diffInDays( Carbon::parse( $this->data['date_from'] ) , false);
        $this->data['from_format'] = $days . ' days ' . (empty($this->data['from_time']) ? '00:00' : $this->data['from_time']);
        $days = $now->diffInDays( Carbon::parse( $this->data['date_to'] ) , false);
        $this->data['to_format'] = $days . ' days ' . (empty($this->data['to_time']) ? '00:00' : $this->data['to_time']);

        if ( ! $this->api ) {
            $this->data['date_from'] .= ' ' . (empty($this->data['from_time']) ? '00:00' : $this->data['from_time']);
            $this->data['date_to']   .= ' ' . (empty($this->data['to_time']) ? '00:00' : $this->data['to_time']);
        }

        $this->data['email'] = $this->data['send_to_email'];

        $daily_time = '00:00';
        if (isset($this->data['daily_time']) && preg_match("/(2[0-4]|[01][1-9]|10):([0-5][0-9])/", $this->data['daily_time']))
            $daily_time = $this->data['daily_time'];

        $this->data['daily_time'] = $daily_time;

        $weekly_time = '00:00';
        if (isset($this->data['weekly_time']) && preg_match("/(2[0-4]|[01][1-9]|10):([0-5][0-9])/", $this->data['weekly_time']))
            $weekly_time = $this->data['weekly_time'];

        $this->data['weekly_time'] = $weekly_time;

        $monthly_time = '00:00';
        if (isset($this->data['monthly_time']) && preg_match("/(2[0-4]|[01][1-9]|10):([0-5][0-9])/", $this->data['monthly_time']))
            $monthly_time = $this->data['monthly_time'];

        $this->data['monthly_time'] = $monthly_time;

        if ( !empty($this->data['id']) && empty(ReportRepo::find($this->data['id'])) ) {
            unset($this->data['id']);
        }

        if (empty($this->data['id'])) {

            $item = ReportRepo::create($this->data + [
                    'user_id'           => $this->user->id,
                    'daily_email_sent'  => date('Y-m-d', strtotime("-1 day")),
                    'weekly_email_sent' => date("Y-m-d", strtotime("{$this->user->week_start_weekday} this week")),
                    'monthly_email_sent' => date("Y-m-d", strtotime("first day this month"))
                ]);
        } else {
            $item = ReportRepo::findWhere(['id' => $this->data['id'], 'user_id' => $this->user->id]);
            if (!empty($item))
                ReportRepo::update($item->id, $this->data);
        }

        if (!empty($item)) {
            if (isset($this->data['devices']) && is_array($this->data['devices']) && !empty($this->data['devices']))
                $item->devices()->sync($this->data['devices']);

            if (isset($this->data['geofences']) && is_array($this->data['geofences']) && !empty($this->data['geofences']))
                $item->geofences()->sync($this->data['geofences']);

            if (isset($this->data['pois']) && is_array($this->data['pois']) && !empty($this->data['pois']))
                $item->pois()->sync($this->data['pois']);
        }

        return ['status' => $this->api ? 1 : 2];
    }

    public function generate($data = NULL)
    {
        $this->checkException('reports', 'view');

        if (is_null($data))
            $data = $this->data;

        ReportFormValidator::validate('create', $this->data);

        $data['date_from'] .= ( empty($data['from_time']) ? '' : ' ' . $data['from_time']);
        $data['date_to']   .= ( empty($data['to_time']) ? '' : ' ' . $data['to_time']);

        $this->validate($data);

        if (!isset($data['generate'])) {
            unset($data['_token']);
            unset($data['from_time']);
            unset($data['to_time']);

            return [
                'status' => 3,
                'url' => route($this->api ? 'api.generate_report' : 'reports.update').'?'.http_build_query($data + ['generate' => 1], '', '&')
            ];
        }

        $report = $this->reportManager->fromRequest($data);
        return $report->download();
    }

    public function doDestroy($id)
    {
        $item = ReportRepo::find($id);

        $this->checkException('reports', 'remove', $item);

        return compact('item');
    }

    public function destroy()
    {
        $id = array_key_exists('report_id', $this->data) ? $this->data['report_id'] : $this->data['id'];

        $item = ReportRepo::find($id);

        $this->checkException('reports', 'remove', $item);

        ReportRepo::delete($id);

        return ['status' => 1];
    }

    public function getType($type)
    {
        $types = $this->getTypes();

        $filtered = array_filter($types, function($value) use ($type){
            return $value['type'] == $type;
        });

        if (empty($filtered))
            throw new \Exception('Not found');

        return reset($filtered);
    }

    public function getTypes()
    {
        $fields = ['geofences', 'speed_limit', 'stops', 'show_addresses', 'zones_instead', 'devices'];

        $types = [];

        foreach ($this->reportManager->getList() as $type => $name)
        {
            $types[$type] = [
                'type' => $type,
                'name' => $name,
                'formats' => [
                    'html', 'xls', 'pdf', 'pdf_land'
                ],
                'fields' => $fields
            ];

            switch ($type) {
                case "1":
                case "2":
                case "16":
                case "42":
                case "43":
                case (string)RoutesSummarizedReport::TYPE_ID:
                case "48":
                case "56":
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'show_addresses', 'zones_instead']);
                    if (in_array($type, [43, RoutesSummarizedReport::TYPE_ID]))
                        $types[$type]['formats'] = ['html'];
                    break;
                case "3":
                case "4":
                case "39":
                case "40":
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'speed_limit']);
                    break;
                case "5":
                case "6":
                case "33":
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'stops']);
                    break;
                case "47":
                    $types[$type]['fields'] = array_diff($fields, ['stops']);
                    break;
                case "31":
                case "44":
                    $types[$type]['fields'] = array_diff($fields, [
                        'speed_limit', 'stops', 'show_addresses', 'zones_instead'
                    ]);
                    break;
                case "7":
                    $types[$type]['fields'] = array_diff($fields, ['speed_limit', 'stops']);
                    break;
                case "8":
                    $types[$type]['formats'][] = 'csv';
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'speed_limit', 'stops']);
                    $types[$type]['parameters'] = [
                        [
                            'title' => trans('validation.attributes.type'),
                            'name'  => 'event_types',
                            'type'  => 'multiselect',
                            'options' => toOptions(
                                collect(Event::getTypeTitles())->pluck('title', 'type')->toArray()
                            ),
                            'validation' => 'array'
                        ],
                    ];
                    break;
                case "9":
                case "29":
                case "62":
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'speed_limit', 'stops', 'show_addresses', 'zones_instead']);
                    break;
                case "10":
                case "11":
                case "12":
                case "13":
                case "37":
                case "38":
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'speed_limit', 'stops']);
                    if (in_array($type, [10, 13]))
                        $types[$type]['formats'] = ['html'];

                    break;
                case "14":
                case "23":
                case "34":
                case "63":
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'stops', 'show_addresses', 'zones_instead']);
                    break;
                case "28":
                    $types[$type]['fields'] = array_diff($fields, ['speed_limit', 'stops', 'show_addresses', 'zones_instead']);

                    $types[$type]['parameters'] = [
                        [
                            'title' => trans('validation.attributes.shift_start'),
                            'name'  => 'shift_start',
                            'type'  => 'select',
                            'default' => '08:00',
                            'options' => toOptions(getSelectTimeRange()),
                            'validation' => 'required|date_format:H:i'
                        ],
                        [
                            'title' => trans('validation.attributes.shift_finish'),
                            'name'  => 'shift_finish',
                            'type'  => 'select',
                            'default' => '17:00',
                            'options' => toOptions(getSelectTimeRange()),
                            'validation' => 'required|date_format:H:i'
                        ],
                        [
                            'title' => trans('validation.attributes.shift_start_tolerance'),
                            'name'  => 'shift_start_tolerance',
                            'type'  => 'select',
                            'options' => toOptions(config('tobuli.minutes')),
                            'validation' => 'required|integer'
                        ],
                        [
                            'title' => trans('validation.attributes.shift_finish_tolerance'),
                            'name'  => 'shift_finish_tolerance',
                            'type'  => 'select',
                            'options' => toOptions(config('tobuli.minutes')),
                            'validation' => 'required|integer'
                        ],
                        [
                            'title' => trans('validation.attributes.excessive_exit'),
                            'name'  => 'excessive_exit',
                            'type'  => 'integer',
                            'default' => 10,
                            'validation' => 'required|integer'
                        ],
                    ];

                    break;
                case GeofencesStopReport::TYPE_ID:
                case GeofencesStopShiftReport::TYPE_ID:
                    $types[$type]['fields'] = array_diff($fields, ['speed_limit', 'show_addresses', 'zones_instead']);

                    $types[$type]['parameters'] = [];

                    if ($type == GeofencesStopShiftReport::TYPE_ID) {
                        $timeSelect = [
                            'type' => 'select',
                            'options' => toOptions(['' => trans('admin.select')] + getSelectTimeRange()),
                            'validation' => 'date_format:H:i'
                        ];

                        for ($i = 1; $i <= 3; $i++) {
                            $types[$type]['parameters'][] = [
                                    'title' => trans('validation.attributes.shift_start') . " #$i",
                                    'name' => 'shift_start_' . $i,
                                ] + $timeSelect;
                            $types[$type]['parameters'][] = [
                                    'title' => trans('validation.attributes.shift_finish') . " #$i",
                                    'name' => 'shift_finish_' . $i,
                                ] + $timeSelect;
                        }
                    }

                    $types[$type]['parameters'][] = [
                        'title' => trans('front.group_geofences'),
                        'name'  => 'group_geofences',
                        'type'  => 'select',
                        'options' => toOptions([0 => trans('global.no'), 1 => trans('global.yes')]),
                        'default' => 0,
                        'validation' => 'required'
                    ];

                    break;
                case "30":
                    $types[$type]['fields'] = array_diff($fields, ['speed_limit', 'stops', 'zones_instead', 'geofences']);

                    $types[$type]['parameters'] = [
                        [
                            'title' => trans('front.ignition_off'),
                            'name'  => 'ignition_off',
                            'type'  => 'select',
                            'default' => 1,
                            'options' => toOptions([
                                '0' => '> 0 '.trans('front.minute_short'),
                                '1' => '> 1 '.trans('front.minute_short'),
                                '2' => '> 2 '.trans('front.minute_short'),
                                '5' => '> 5 '.trans('front.minute_short'),
                                '10' => '> 10 '.trans('front.minute_short'),
                                '20' => '> 20 '.trans('front.minute_short'),
                                '30' => '> 30 '.trans('front.minute_short'),
                                '60' => '> 1 '.trans('front.hour_short'),
                                '120' => '> 2 '.trans('front.hour_short'),
                                '300' => '> 5 '.trans('front.hour_short'),
                            ]),
                            'validation' => 'required|integer'
                        ],
                    ];
                    break;
                case "32":
                    $types[$type]['fields'] = ['devices'];

                    $types[$type]['parameters'] = [
                        [
                            'title' => trans('validation.attributes.status'),
                            'name'  => 'status',
                            'type'  => 'select',
                            'default' => 1,
                            'options' => toOptions([
                                '' => trans('front.none'),
                                SentCommandsReport::STATUS_FAIL => SentCommandsReport::STATUS_FAIL,
                                SentCommandsReport::STATUS_SENT => SentCommandsReport::STATUS_SENT,
                            ]),
                            'validation' => 'in:'.SentCommandsReport::STATUS_SENT.','.SentCommandsReport::STATUS_FAIL
                        ],
                    ];
                    break;
                case "35":
                case "36":
                    $types[$type]['fields'] = [];
                    break;
                case "46":
                    $types[$type]['fields'] = ['devices'];

                    $all = ['all' => 'All'];

                    $types[$type]['parameters'] = [
                        [
                            'title'      => trans('validation.attributes.expense_type'),
                            'name'       => 'expense_type',
                            'type'       => 'select',
                            'options'    => toOptions($all + DeviceExpensesType::all()->pluck('name', 'id')->toArray()),
                            'validation' => 'required',
                        ],
                        [
                            'title'      => trans('validation.attributes.supplier'),
                            'name'       => 'supplier',
                            'type'       => 'select',
                            'options'    => toOptions(
                                $all + array_pluck(
                                    DeviceExpense::select('supplier')->distinct()->get(), 'supplier', 'supplier'
                                )
                            ),
                            'validation' => 'required',
                        ],
                    ];
                    break;
                case "50":
                    $types[$type]['formats'] = ['html'];

                    $types[$type]['parameters'] = [
                        [
                            'title' => trans('validation.attributes.status'),
                            'name'  => 'status',
                            'type'  => 'select',
                            'default' => 'all',
                            'options' => toOptions(getCompletionStatus()),
                            'validation' => 'required'
                        ],
                    ];

                    break;
                case "54":
                case "55":
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'speed_limit', 'stops']);

                    $pois = PoiRepo::whereUserId($this->user->id);
                    $pois->map(function($item) {
                            $item['title'] = $item['name'];
                            return $item;
                        })
                        ->only('id', 'title')
                        ->all();

                    if (!$this->api)
                        $pois = groupPois($pois, $this->user);

                    if ($type == '54') {
                        $duration_title = trans('validation.attributes.stop_duration_longer_than') . ' (' . trans('front.minutes') . ')';
                        $duration_name = 'stop_duration';
                    } else {
                        $duration_title = trans('validation.attributes.idle_duration_longer_than') . ' (' . trans('front.minutes') . ')';
                        $duration_name = 'idle_duration';
                    }

                    $types[$type]['parameters'] = [
                        [
                            'title' => $duration_title,
                            'name'  => $duration_name,
                            'type'  => 'integer',
                            'default' => null,
                            'validation' => 'required'
                        ],
                        [
                            'title' => trans('validation.attributes.distance_tolerance')  . ' (' . trans('front.mt') . ')',
                            'name'  => 'distance_tolerance',
                            'type'  => 'integer',
                            'default' => null,
                            'validation' => 'required'
                        ],
                        [
                            'title' => trans('validation.attributes.pois'),
                            'name'  => 'pois',
                            'type'  => $this->api ? 'multiselect' : 'multiselect-group',
                            'default' => null,
                            'options' => $pois,
                            'validation' => 'required',
                            'param_omit' => true,
                        ],
                    ];

                    break;

                case "59":
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'speed_limit', 'stops']);

                    $types[$type]['parameters'] = [
                        [
                            'title' => trans('validation.attributes.speed_limit_tolerance') . ' (' . trans('front.kph') . ')',
                            'name'  => 'speed_limit_tolerance',
                            'type'  => 'integer',
                            'default' => null,
                            'validation' => 'numeric'
                        ]
                    ];

                    break;

                case "60":
                    $types[$type]['fields'] = array_diff($fields, ['speed_limit', 'stops', 'show_addresses', 'zones_instead']);

                    $types[$type]['parameters'] = [
                        [
                            'title' => trans('validation.attributes.speed_limit') . ' (' . trans('front.kph') . ')',
                            'name'  => 'speed_break',
                            'type'  => 'integer',
                            'default' => '10',
                            'validation' => 'required|numeric'
                        ],
                        [
                            'title' => trans('validation.attributes.distance_limit') . ' (' . trans('front.mt') . ')',
                            'name'  => 'distance_limit',
                            'type'  => 'integer',
                            'default' => '100',
                            'validation' => 'required|numeric'
                        ],
                        [
                            'title' => trans('validation.attributes.shift_start'),
                            'name'  => 'shift_start',
                            'type'  => 'string',
                            'default' => '05:30',
                            'validation' => 'required|date_format:H:i'
                        ]
                    ];

                    break;
                case "64":
                    $types[$type]['formats'] = ['html', 'pdf', 'pdf_land'];
                    $types[$type]['fields'] = array_diff($fields, ['geofences', 'speed_limit']);
                    break;
            }

            if ( ! empty(ReportManager::$types[$type]))
                $types[$type]['fields'][] = 'metas';

            $types[$type]['fields'] = array_values($types[$type]['fields']);
        }

        return array_values($types);
    }

    public function validate( & $data)
    {
        $validator = Validator::make($data, [
            'type' => 'required',
            'metas' => 'array'
        ]);

        if ($validator->fails())
            throw new ValidationException(['type' => $validator->errors()->first()]);

        if (empty($data['send_to_email']))
            $data['send_to_email'] = '';
        $arr['send_to_email'] = array_flip(explode(';', $data['send_to_email']));
        unset($arr['send_to_email']['']);
        $arr['send_to_email'] = array_flip($arr['send_to_email']);
        $arr['send_to_email'] = array_map('trim', $arr['send_to_email']);

        # Regenerate string
        $data['send_to_email'] = implode(';', $arr['send_to_email']);

        $validator = Validator::make($arr, [
            'send_to_email' => 'array_max:'.config('tobuli.limits.report_emails'),
            'send_to_email.*' => 'email',
        ]);

        if ($validator->fails())
            throw new ValidationException(['send_to_email' => $validator->errors()->first()]);

        if (!empty($data['daily']) || !empty($data['weekly']) || !empty($data['monthly'])) {
            $validator = Validator::make($arr, ['send_to_email' => 'required']);
            if ($validator->fails())
                throw new ValidationException(['send_to_email' => $validator->errors()->first()]);
        }

        if (strtotime($data['date_from']) > strtotime($data['date_to'])) {
            $message = str_replace(':attribute', trans('validation.attributes.date_to'), trans('validation.after'));
            $message = str_replace(':date', trans('validation.attributes.date_from'), $message);
            throw new ValidationException(['date_to' => $message]);
        }

        if (in_array($data['type'], ['7', '15', '20', '28', '31', '44', '67', '68'])) {
            $validator = Validator::make($data, ['geofences' => 'required']);
            if ($validator->fails())
                throw new ValidationException(['geofences' => $validator->errors()->first()]);
        }

        if (in_array($data['type'], ['5', '6'])) {
            $validator = Validator::make($data, ['speed_limit' => 'required']);
            if ($validator->fails())
                throw new ValidationException(['speed_limit' => $validator->errors()->first()]);
        }

        if ($data['type'] == '47') {
            $validator = Validator::make($data, ['speed_limit' => 'required', 'geofences' => 'required']);
            if ($validator->fails())
                throw new ValidationException($validator->errors('speed_limit')->toArray());
        }

        if ($data['type'] == '25') {
            $validator = Validator::make($data, ['devices' => 'same_protocol']);
            if ($validator->fails())
                throw new ValidationException(['devices' => $validator->errors()->first()]);
        }

        $type = $this->getType($data['type']);

        if ( ! empty($type['parameters'])) {
            $parameters = [];
            $rules = [];
            foreach ($type['parameters'] as $parameter)
            {
                if (empty($parameter['param_omit']))
                    $parameters[] = $parameter['name'];

                if (empty($parameter['validation']))
                    continue;

                //html attribute name to validation name
                $name = preg_replace(['/\[\]/', '/\[([^\[\]]+)\]/'], ['.*', '.$1'], $parameter['name']);

                $rules[$name] = $parameter['validation'];
            }

            if ($rules) {
                $validator = Validator::make($data, $rules);
                if ($validator->fails())
                    throw new ValidationException($validator->errors());
            }

            $data['parameters'] = array_only($data, $parameters);
        }
    }
}