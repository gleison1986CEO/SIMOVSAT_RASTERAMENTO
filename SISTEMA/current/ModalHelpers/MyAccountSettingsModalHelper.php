<?php namespace ModalHelpers;

use Session;
use App\Exceptions\DemoAccountException;
use CustomFacades\Repositories\DeviceGroupRepo;
use CustomFacades\Repositories\SmsEventQueueRepo;
use CustomFacades\Repositories\TimezoneRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Validators\SMSGatewayFormValidator;
use CustomFacades\Validators\UserAccountFormValidator;
use CustomFacades\Validators\UserAccountSettingsFormValidator;
use Illuminate\Support\Facades\DB;


class MyAccountSettingsModalHelper extends ModalHelper
{
    private $data_group = [];
    
    public function editData()
    {
        $item = UserRepo::find($this->user->id)->toArray();
        $timezones = TimezoneRepo::order()->pluck('title', 'id')->all();
        $groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id], 'title');

        $sms_queue_count = SmsEventQueueRepo::countwhere(['user_id' => $this->user->id]);
        $user_dst = DB::table('users_dst')->where('user_id', '=', $this->user->id)->first();

        if (! $item['timezone_id']) {
            $item['timezone_id'] = 17;
        }

        if (! is_array($item['sms_gateway_params']) || ! array_key_exists('request_method', $item['sms_gateway_params'])) {
            $item['sms_gateway_params']['request_method'] = null;
        }

        if (! is_array($item['sms_gateway_params']) || ! array_key_exists('encoding', $item['sms_gateway_params'])) {
            $item['sms_gateway_params']['encoding'] = null;
        }

        if (! is_array($item['sms_gateway_params']) || ! array_key_exists('authentication', $item['sms_gateway_params'])) {
            $item['sms_gateway_params']['authentication'] = null;
        }

        if (! is_array($item['sms_gateway_params']) || ! array_key_exists('custom_headers', $item['sms_gateway_params'])) {
            $item['sms_gateway_params']['custom_headers'] = null;
        }

        if (! is_array($item['sms_gateway_params']) || ! array_key_exists('username', $item['sms_gateway_params'])) {
            $item['sms_gateway_params']['username'] = null;
        }

        $units_of_distance = [
            'km' => trans('front.kilometer'),
            'mi' => trans('front.mile'),
            'nm' => trans('front.nautical_mile')
        ];

        $units_of_capacity = [
            'lt' => trans('front.liter'),
            'gl' => trans('front.gallon')
        ];

        $units_of_altitude = [
            'mt' => trans('front.meter'),
            'ft' => trans('front.feet')
        ];

        $request_method_select = [
            'get' => 'GET',
            'post' => 'POST',
            'app' => trans('front.sms_gateway_app'),
            'plivo' => 'Plivo'
        ];

        if (settings('sms_gateway.enabled')) {
            $request_method_select = ['server' => 'Server gateway'] + $request_method_select;
        }

        $encoding_select = [0 => trans('global.no'), 'json' => 'JSON'];
        $authentication_select = [0 => trans('global.no'), 1 => trans('global.yes')];

        $dst_types = getDSTTypes();
        $months = getMonths();
        $weekdays = getWeekdays();
        $week_pos = getWeekPositions();
        $week_start_days = getWeekStartDays();
        $dst_countries = getDSTCountries();


        if ($this->api) {
            $timezones = apiArray($timezones);
            $units_of_distance = apiArray($units_of_distance);
            $units_of_capacity = apiArray($units_of_capacity);
            $units_of_altitude = apiArray($units_of_altitude);
            $request_method_select = apiArray($request_method_select);
            $encoding_select = apiArray($encoding_select);
            $authentication_select = apiArray($authentication_select);
            $dst_types = apiArray($dst_types);
            $months = apiArray($months);
            $dst_countries = apiArray($dst_countries);
            $week_start_days = apiArray($week_start_days);
            $week_pos = apiArray($week_pos);
            $weekdays = apiArray($weekdays);
        }

        if ($this->api) {
            return compact('item', 'timezones', 'units_of_distance',
                'units_of_capacity', 'units_of_altitude', 'groups',
                'sms_queue_count', 'request_method_select', 'encoding_select',
                'authentication_select', 'dst_types', 'user_dst',
                'months', 'weekdays', 'week_pos', 'dst_countries',
                'week_start_days');
        }

        if (empty($widgets = UserRepo::getSettings($this->user->id, 'widgets'))) {
            $widgets = settings('widgets');
        }

        $dashboard = getUserDashboardSettings($this->user);

        return compact('item', 'timezones', 'units_of_distance',
            'units_of_capacity', 'units_of_altitude', 'groups',
            'sms_queue_count', 'request_method_select', 'encoding_select',
            'authentication_select',
            'dst_types', 'user_dst', 'months', 'weekdays',
            'week_pos', 'dst_countries', 'week_start_days',
            'widgets', 'dashboard');
    }

    public function edit()
    {
        if (isDemoUser()) {
            throw new DemoAccountException();
        }

        $this->data['sms_gateway'] = (isset($this->data['sms_gateway']) && $this->data['sms_gateway']);
        $this->data['sms_gateway_url'] = isset($this->data['sms_gateway_url']) ? $this->data['sms_gateway_url'] : '';
        $item = $this->user;
        $user_dst = DB::table('users_dst')->where('user_id', '=', $this->user->id)->first();


        if ( ! empty($this->data['sms_gateway']) && isset($this->data['request_method'])) {
            SMSGatewayFormValidator::validate($this->data['request_method'], $this->data);
        }

        UserAccountSettingsFormValidator::validate('update', $this->data, $item->id);

        $array = auth()->user()->map_controls->getArray();

        $update = [
            'sms_gateway' => $this->data['sms_gateway'],
            'sms_gateway_url' => $this->data['sms_gateway_url'],
            'unit_of_distance' => $this->data['unit_of_distance'],
            'unit_of_capacity' => $this->data['unit_of_capacity'],
            'unit_of_altitude' => $this->data['unit_of_altitude'],
            'timezone_id' => $this->data['timezone_id'],
            'week_start_day' => (isset($this->data['week_start_day']) ? $this->data['week_start_day'] : 1),
            'map_controls' => $array,
        ];

        if (isset($this->data['request_method'])) {
            $fields = [
                'request_method',
                'authentication',
                'username',
                'password',
                'encoding',
                'auth_id',
                'auth_token',
                'senders_phone',
                'custom_headers'
            ];
            $update['sms_gateway_params'] = [];

            foreach ($fields as $field) {
                $value = '';

                if (isset($this->data[$field])) {
                    $value = $this->data[$field];
                } else {
                    if (isset($item->sms_gateway_params[$field])) {
                        $value = $item->sms_gateway_params[$field];
                    }
                }

                $update['sms_gateway_params'][$field] = $value;
            }
        }

        UserRepo::update($item->id, $update);

        if (isset($this->data['dst_type'])) {
            # Daylight saving time
            if ($this->data['dst_type'] == 'exact' || $this->data['dst_type'] == 'other' || $this->data['dst_type'] == 'automatic') {
                $dst_arr = [
                    'user_id' => $this->user->id,
                    'country_id' => NULL,
                    'type' => $this->data['dst_type'],
                    'date_from' => NULL,
                    'date_to' => NULL,
                    'month_from' => NULL,
                    'month_to' => NULL,
                    'week_pos_from' => NULL,
                    'week_pos_to' => NULL,
                    'week_day_from' => NULL,
                    'week_day_to' => NULL,
                    'time_from' => NULL,
                    'time_to' => NULL
                ];

                if ($this->data['dst_type'] == 'exact') {
                    $dst_arr['date_from'] = $this->data['date_from'];
                    $dst_arr['date_to'] = $this->data['date_to'];
                } elseif ($this->data['dst_type'] == 'other') {
                    $dst_arr['month_from'] = $this->data['month_from'];
                    $dst_arr['month_to'] = $this->data['month_to'];
                    $dst_arr['week_pos_from'] = $this->data['week_pos_from'];
                    $dst_arr['week_pos_to'] = $this->data['week_pos_to'];
                    $dst_arr['week_day_from'] = $this->data['week_day_from'];
                    $dst_arr['week_day_to'] = $this->data['week_day_to'];
                    $dst_arr['time_from'] = $this->data['time_from'];
                    $dst_arr['time_to'] = $this->data['time_to'];
                } elseif ($this->data['dst_type'] == 'automatic') {
                    $dst_arr['country_id'] = $this->data['dst_country_id'];
                }

                if (! empty($user_dst)) {
                    $unchanged = array_intersect_assoc(json_decode(json_encode($user_dst), TRUE), $dst_arr);

                    if (count($unchanged) != count($dst_arr)) {
                        DB::table('users_dst')->where('user_id', '=', $this->user->id)->update($dst_arr);
                    }
                } else {
                    DB::table('users_dst')->insert($dst_arr);
                }
            } else {
                DB::table('users_dst')->where('user_id', '=', $this->user->id)->delete();
            }
        }

        # Object groups
        if ($this->api) {
            $arr = [];
            $groups = DeviceGroupRepo::getWhere(['user_id' => $item->id]);

            if (!$groups->isEmpty())
                $groups = $groups->pluck('id', 'id')->all();


            $this->data_group = [];

            if (isset($this->data['groups'])) {
                $this->data_group = $this->data['groups'];

                if ( ! is_array($this->data_group)) {
                    $this->data_group = json_decode($this->data_group, TRUE);
                }
            }

            foreach ($this->data_group as $key => $group) {
                $title = $group['title'];
                $id = $group['id'];

                if (empty($title)) {
                    continue;
                }

                if (array_key_exists($group['id'], $groups)) {
                    $arr[$id] = $id;
                    DeviceGroupRepo::updateWhere(['id' => $id, 'user_id' => $this->user->id], ['title' => $title]);
                } else {
                    $itemd = DeviceGroupRepo::create(['title' => $title, 'user_id' => $item->id]);
                    $id = $itemd->id;
                    $arr[$id] = $id;
                }
            }

            DeviceGroupRepo::deleteUsersWhereNotIn($arr, $item->id);
        }

        if ( ! $this->api) {
            $widgets = empty($this->data['widgets']) ? null : $this->data['widgets'];
            UserRepo::setSettings($item->id, 'widgets', $widgets);
            UserRepo::setSettings($item->id, 'dashboard', array_merge(settings('dashboard'), $this->data['dashboard']));
        }

        return ['status' => 1, 'id' => $item->id];
    }

    public function changePassword()
    {
        if (isDemoUser()) {
            throw new DemoAccountException();
        }

        $item = UserRepo::find($this->user->id);

        $this->data['email'] = $item->email;

        UserAccountFormValidator::validate('update', $this->data, $item->id);

        $update = [
            'email' => $this->data['email']
        ];

        if ( ! empty($this->data['password'])) {
            $update['password'] = $this->data['password'];
        }

        UserRepo::update($item->id, $update);

        return ['status' => 1, 'id' => $item->id];
    }
}