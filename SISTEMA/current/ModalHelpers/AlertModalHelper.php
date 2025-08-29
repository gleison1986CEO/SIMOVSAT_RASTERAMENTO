<?php namespace ModalHelpers;

use Formatter;
use CustomFacades\ModalHelpers\CustomEventModalHelper;
use CustomFacades\ModalHelpers\SendCommandModalHelper;
use CustomFacades\Repositories\AlertDeviceRepo;
use CustomFacades\Repositories\AlertGeofenceRepo;
use CustomFacades\Repositories\AlertRepo;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\EventCustomRepo;
use CustomFacades\Repositories\GeofenceRepo;
use CustomFacades\Repositories\PoiRepo;
use CustomFacades\Repositories\UserGprsTemplateRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Validators\AlertFormValidator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Protocols\Commands;
use Tobuli\Protocols\Manager as ProtocolsManager;

class AlertModalHelper extends ModalHelper
{
    public function get()
    {
        try {
            $this->checkException('alerts', 'view');
        } catch (\Exception $e) {
            return ['alerts' => []];
        }

        if ($this->api) {
            $alerts = AlertRepo::getWithWhere(['devices', 'drivers', 'geofences', 'events_custom'], ['user_id' => $this->user->id]);
            $alerts = $alerts->toArray();

            foreach ($alerts as $key => $alert) {
                $drivers = [];
                foreach ($alert['drivers'] as $driver)
                    array_push($drivers, $driver['id']);

                $alerts[$key]['drivers'] = $drivers;

                $devices = [];
                foreach ($alert['devices'] as $device)
                    array_push($devices, $device['id']);

                $alerts[$key]['devices'] = $devices;

                $geofences = [];
                foreach ($alert['geofences'] as $geofence)
                    array_push($geofences, $geofence['id']);

                $alerts[$key]['geofences'] = $geofences;

                $events_custom = [];
                foreach ($alert['events_custom'] as $event)
                    array_push($events_custom, $event['id']);

                $alerts[$key]['events_custom'] = $events_custom;
            }
        } else {
            $alerts = AlertRepo::getWhere(['user_id' => $this->user->id]);
        }

        return compact('alerts');
    }

    public function createData()
    {
        $this->checkException('alerts', 'create');

        $devices = $this->user->devices;
        $geofences = GeofenceRepo::whereUserId($this->user->id)->pluck('name', 'id')->all();

        if (empty($devices))
            throw new ValidationException(['id' => trans('front.must_have_one_device')]);

        $types = $this->getTypesWithAttributes();
        $schedules = $this->getSchedules();
        $notifications = $this->getNotifications();

        $alert_zones = [
            '1' => trans('front.zone_in'),
            '2' => trans('front.zone_out')
        ];

        if ($this->api) {
            $devices = apiArray($devices->pluck('name', 'id')->all());
            $geofences = apiArray($geofences);
            $alert_zones = apiArray($alert_zones);
        } else {
            $devices = groupDevices($devices, $this->user);
        }

        return compact(
            'devices',
            'geofences',

            'types',
            'schedules',
            'notifications',
            'alert_zones'
        );
    }

    public function create()
    {
        $this->checkException('alerts', 'store');

        $this->validate('create');

        beginTransaction();
        try {
            $alert = $this->user->alerts()->create($this->data);

            $alert->devices()->sync(array_get($this->data, 'devices', []));
            $alert->geofences()->sync(array_get($this->data, 'geofences', []));
            $alert->drivers()->sync(array_get($this->data, 'drivers', []));
            $alert->zones()->sync(array_get($this->data, 'zones', []));
            $alert->pois()->sync(array_get($this->data, 'pois', []));

            $events_custom = array_get($this->data, 'events_custom', []);
            if ($events_custom) {
                $protocols = DeviceRepo::getProtocols($this->data['devices']);
                $events = EventCustomRepo::whereProtocols($events_custom, $protocols->pluck('protocol')->all());
                $events_custom = $events->pluck('id')->all();
            }
            $alert->events_custom()->sync($events_custom);
        }
        catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }

        commitTransaction();

        return ['status' => 1];
    }

    public function editData()
    {
        $id = array_key_exists('alert_id', $this->data) ? $this->data['alert_id'] : request()->route('alerts');
        $id = $id ? $id : request()->route('id');

        $item = AlertRepo::findWithAttributes($id);

        $this->checkException('alerts', 'edit', $item);

        $devices = $this->user->devices;

        if (empty($devices))
            throw new ValidationException(['id' => trans('front.must_have_one_device')]);

        $types = $this->getTypesWithAttributes($item);
        $schedules = $this->getSchedules($item);
        $notifications = $this->getNotifications($item);
        $commands = SendCommandModalHelper::getCommands($devices);
        $geofences = GeofenceRepo::whereUserId($this->user->id)->pluck('name', 'id')->all();

        $alert_zones = [
            '1' => trans('front.zone_in'),
            '2' => trans('front.zone_out')
        ];

        if ($this->api) {
            $devices     = apiArray($devices->pluck('name', 'id')->all());
            $geofences   = apiArray($geofences);
            $alert_zones = apiArray($alert_zones);
        } else {
            $devices = groupDevices($devices, $this->user);
        }

        return compact(
            'item',
            'devices',
            'geofences',

            'types',
            'schedules',
            'notifications',
            'alert_zones',
            'commands'
        );
    }

    public function edit()
    {
        $alert = AlertRepo::findWithAttributes($this->data['id']);

        $this->checkException('alerts', 'update', $alert);

        $this->validate('update');

        beginTransaction();
        try {
            AlertRepo::update($alert->id, $this->data);

            $alert->devices()->sync(array_get($this->data, 'devices', []));
            $alert->geofences()->sync(array_get($this->data, 'geofences', []));
            $alert->drivers()->sync(array_get($this->data, 'drivers', []));
            $alert->zones()->sync(array_get($this->data, 'zones', []));
            $alert->pois()->sync(array_get($this->data, 'pois', []));

            $events_custom = array_get($this->data, 'events_custom', []);
            if ($events_custom) {
                $protocols = DeviceRepo::getProtocols($this->data['devices']);
                $events = EventCustomRepo::whereProtocols($events_custom, $protocols->pluck('protocol')->all());
                $events_custom = $events->pluck('id')->all();
            }
            $alert->events_custom()->sync($events_custom);
        }
        catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }

        commitTransaction();

        return ['status' => 1];
    }

    private function validate($type)
    {
        $alert_id = array_get($this->data, 'id');

        AlertFormValidator::validate($type, $this->data, $alert_id);

        foreach (array_get($this->data, 'schedules', []) as $weekday => $schedule)
        {
            $validator = null;

            switch ($weekday) {
                case 'monday':
                case 'tuesday':
                case 'wednesday':
                case 'thursday':
                case 'friday':
                case 'saturday':
                case 'sunday':
                    $validator = Validator::make($this->data, [
                        "schedules.$weekday"   => 'required|array',
                        "schedules.$weekday.*" => 'in:' . implode(',',array_keys(getSelectTimeRange()))
                    ]);
                    break;
                default:
                    throw new ValidationException(["schedules.$weekday" => 'Wrong week day.']);
            }

            if ($validator && $validator->fails()) {
                throw new ValidationException(['schedule' => $validator->errors()->first()]);
            }
        }

        foreach (array_get($this->data, 'notifications', []) as $name => $notification)
        {
            $validator = null;
            $active = array_get($notification, 'active', false) ? true : false;

            switch ($name) {
                case 'silent':
                    if ($active) {
                        $validator = Validator::make($notification, [
                            'input' => 'required|integer|min:1',
                        ]);
                    }
                    break;
                case 'color':
                    if ($active) {
                        $notification['input'] = array_get($notification, 'input');
                        $validator = Validator::make($notification, [
                            'input'   => 'required|css_color',
                        ]);
                    }

                    break;
                case 'sound':
                case 'push':
                case 'auto_hide':
                    break;
                case 'email':
                    if ($active) {
                        $notification['input'] = semicol_explode(array_get($notification, 'input'));
                        $validator = Validator::make($notification, [
                            'input'   => 'required|array_max:'.config('tobuli.limits.alert_emails'),
                            'input.*' => 'email'
                        ]);
                    }

                    break;
                case 'webhook':
                    if ($active) {
                        $notification['input'] = semicol_explode(array_get($notification, 'input'));
                        $validator = Validator::make($notification, [
                            'input'   => 'required|array_max:' . config('tobuli.limits.alert_webhooks'),
                            'input.*' => 'url'
                        ]);
                    }

                    break;
                case 'sms':
                    if ($active) {
                        $notification['input'] = semicol_explode(array_get($notification, 'input'));
                        $validator = Validator::make($notification, [
                            'input' => 'required|array_max:' . config('tobuli.limits.alert_phones')
                        ]);
                    }

                    break;
                case 'sharing_email':
                    if ($active) {
                        $notification['input'] = semicol_explode(array_get($notification, 'input'));
                        $validator = Validator::make($notification, [
                            'input' => 'required|array',
                            'input.*' => 'email'
                        ]);
                    }

                    break;
                case 'sharing_sms':
                    if ($active) {
                        $notification['input'] = semicol_explode(array_get($notification, 'input'));
                        $validator = Validator::make($notification, [
                            'input' => 'required|array'
                        ]);
                    }

                    break;
                default:
                    throw new ValidationException(["notifications.$name" => 'Notification type not supported.']);
            }

            if ($validator && $validator->fails())
                throw new ValidationException(["notifications.$name.input" => $validator->errors()->first()]);

            $this->data['notifications'][$name] = array_only($this->data['notifications'][$name], ['active', 'input']);
        }

        if (array_get($this->data, 'command.active'))
        {
            $devices = DeviceRepo::getWhereIn($this->data['devices']);
            $commands = SendCommandModalHelper::getCommands($devices);
            $rules = Commands::validationRules(array_get($this->data, 'command.type'), $commands);
            $validator = Validator::make($this->data, $rules);
            if ($validator->fails()) {
                throw new ValidationException($validator->messages());
            }

            if ($rules) {
                $this->data['command'] = array_merge(
                    array_only($this->data, array_keys($rules)),
                    $this->data['command']
                );
            }
        }

    }

    public function changeActive()
    {
        $item = AlertRepo::find($this->data['id']);

        $this->checkException('alerts', 'active', $item);

        $active = (isset($this->data['active']) && filter_var($this->data['active'], FILTER_VALIDATE_BOOLEAN)) ? 1 : 0;

        AlertRepo::update($item->id, ['active' => $active]);

        return ['status' => 1];
    }

    public function doDestroy($id) {
        $item = AlertRepo::find($id);

        $this->checkException('alerts', 'remove', $item);

        return compact('item');
    }

    public function destroy()
    {
        $id = array_key_exists('alert_id', $this->data) ? $this->data['alert_id'] : $this->data['id'];

        $item = AlertRepo::findWithAttributes($id);

        $this->checkException('alerts', 'remove', $item);

        AlertRepo::delete($id);

        return ['status' => 1];
    }

    public function getTypesWithAttributes($alert = null)
    {
        $drivers = UserRepo::getDrivers($this->user->id);
        $drivers->map(function($item) {
            $item['title'] = $item['name'];
            return $item;
        })->only('id', 'title')->all();

        $geofences = GeofenceRepo::whereUserId($this->user->id);
        $geofences->map(function($item) {
            $item['title'] = $item['name'];
            return $item;
        })->only('id', 'title')->all();

        $pois = PoiRepo::whereUserId($this->user->id);
        $pois->map(function($item) {
            $item['title'] = $item['name'];
            return $item;
        })->only('id', 'title')->all();

        $events_custom = $alert ? CustomEventModalHelper::getGroupedEvents($alert->devices->pluck('id')->all()) : [];

        $types = self::getTypes();

        foreach ($types as & $type)
        {
            switch ($type['type']) {
                case 'overspeed':
                    $type['attributes'] = [
                        [
                            'name'    => 'overspeed',
                            'title'   => trans('validation.attributes.overspeed') . "(" . Formatter::speed()->getUnit() . ")",
                            'type'    => 'integer',
                            'default' => $alert ? $alert->overspeed : '',
                        ],
                    ];
                    break;
                case 'time_duration':
                    $type['attributes'] = [
                        [
                            'name'    => 'time_duration',
                            'title'   => trans('validation.attributes.time_duration') . '(' . trans('front.minutes') . ')',
                            'type'    => 'integer',
                            'default' => $alert ? $alert->time_duration : '',
                        ],
                    ];
                    break;
                case 'stop_duration':
                    $type['attributes'] = [
                        [
                            'name'    => 'stop_duration',
                            'title'   => trans('validation.attributes.stop_duration_longer_than') . '(' . trans('front.minutes') . ')',
                            'type'    => 'integer',
                            'default' => $alert ? $alert->stop_duration : '',
                        ],
                    ];
                    break;
                case 'offline_duration':
                    $type['attributes'] = [
                        [
                            'name'    => 'offline_duration',
                            'title'   => trans('validation.attributes.offline_duration_longer_than') . '(' . trans('front.minutes') . ')',
                            'type'    => 'integer',
                            'default' => $alert ? $alert->offline_duration : '',
                        ],
                    ];
                    break;
                case 'idle_duration':
                    $type['attributes'] = [
                        [
                            'name'    => 'idle_duration',
                            'title'   => trans('validation.attributes.idle_duration_longer_than') . '(' . trans('front.minutes') . ')',
                            'type'    => 'integer',
                            'default' => $alert ? $alert->idle_duration : '',
                        ],
                    ];
                    break;
                case 'ignition_duration':
                        $type['attributes'] = [
                            [
                                'name'    => 'ignition_duration',
                                'title'   => trans('validation.attributes.ignition_duration_longer_than') . '(' . trans('front.minutes') . ')',
                                'type'    => 'integer',
                                'default' => $alert ? $alert->ignition_duration : '',
                            ],
                        ];

                        if ($this->user->perm('checklist', 'view')) {
                            $type['attributes'][] = [
                                'name'    => 'pre_start_checklist_only',
                                'title'   => trans('global.pre_start_checklist'),
                                'type'    => 'select',
                                'options' => [
                                    [
                                        'id' => 0,
                                        'title' => trans('global.no')
                                    ],
                                    [
                                        'id' => 1,
                                        'title' => trans('global.yes')
                                    ],
                                ],
                                'default' => $alert ? $alert->pre_start_checklist_only : 0,
                                'description' => trans('global.pre_start_checklist_alert_description'),
                            ];
                        }
                        break;
                case 'driver':
                    $type['attributes'] = [
                        [
                            'name'    => 'drivers',
                            'title'   => trans('front.drivers') . ':',
                            'type'    => 'multiselect',
                            'options' => $drivers,
                            'default' => $alert ? $alert->drivers->pluck('id')->all() : [],
                        ],
                    ];
                    break;
                case 'driver_unauthorized':
                    $type['attributes'] = [
                        [
                            'name'    => 'authorized',
                            'title'   => trans('validation.attributes.authorized'),
                            'type'    => 'select',
                            'options' => [
                                [
                                    'id' => 0,
                                    'title' => trans('global.no')
                                ],
                                [
                                    'id' => 1,
                                    'title' => trans('global.yes')
                                ],
                            ],
                            'default' => $alert ? $alert->authorized : '0',
                        ],
                    ];
                    break;
                case 'geofence_in':
                case 'geofence_out':
                case 'geofence_inout':
                    $type['attributes'] = [
                        [
                            'name'    => 'geofences',
                            'title'   => trans('validation.attributes.geofences'),
                            'type'    => 'multiselect',
                            'options' => $geofences,
                            'default' => $alert ? $alert->geofences->pluck('id')->all() : [],
                        ],
                    ];
                    break;
                case 'custom':
                    $type['attributes'] = [
                        [
                            'name'        => 'events_custom',
                            'title'       => trans('validation.attributes.event'),
                            'type'        => 'multiselect',
                            'options'     => $events_custom,
                            'default'     => $alert ? $alert->events_custom->pluck('id')->all() : [],
                            'description' => trans('front.alert_events_tip'),
                        ],
                        [
                            'name'        => 'continuous_duration',
                            'title'       => trans('validation.attributes.continuous_duration') . "(" . trans('front.second_short') . ")",
                            'type'        => 'integer',
                            'default'     => $alert ? $alert->continuous_duration : 0,
                        ],
                    ];
                    break;
                case 'distance':
                    $type['attributes'] = [
                        [
                            'name'        => 'distance',
                            'title'       => trans('validation.attributes.distance_limit') . "(" . Formatter::distance()->getUnit() . ")",
                            'type'        => 'integer',
                            'default'     => $alert ? $alert->distance : 0,
                        ],
                        [
                            'name'        => 'period',
                            'title'       => trans('validation.attributes.period') . "(" . trans('global.days') . ")",
                            'type'        => 'integer',
                            'default'     => $alert ? $alert->period : 0,
                        ],
                    ];
                    break;
                case 'poi_stop_duration':
                    $type['attributes'] = [
                        [
                            'name'    => 'stop_duration',
                            'title'   => trans('validation.attributes.stop_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            'type'    => 'integer',
                            'default' => $alert ? $alert->stop_duration : '',
                        ],
                        [
                            'name'    => 'distance_tolerance',
                            'title'   => trans('validation.attributes.distance_tolerance') . ' (' . trans('front.mt') . ')',
                            'type'    => 'integer',
                            'default' => $alert ? $alert->distance_tolerance : '',
                        ],
                        [
                            'name'    => 'pois',
                            'title'   => trans('validation.attributes.pois'),
                            'type'    => $this->api ? 'multiselect' : 'multiselect-group',
                            'options' => $this->api ? $pois : groupPois($pois, $this->user),
                            'default' => $alert ? $alert->pois->pluck('id')->all() : [],
                        ],
                    ];
                    break;
                case 'poi_idle_duration':
                    $type['attributes'] = [
                        [
                            'name'    => 'idle_duration',
                            'title'   => trans('validation.attributes.idle_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            'type'    => 'integer',
                            'default' => $alert ? $alert->idle_duration : '',
                        ],
                        [
                            'name'    => 'distance_tolerance',
                            'title'   => trans('validation.attributes.distance_tolerance') . ' (' . trans('front.mt') . ')',
                            'type'    => 'integer',
                            'default' => $alert ? $alert->distance_tolerance : '',
                        ],
                        [
                            'name'    => 'pois',
                            'title'   => trans('validation.attributes.pois'),
                            'type'    => $this->api ? 'multiselect' : 'multiselect-group',
                            'options' => $this->api ? $pois : groupPois($pois, $this->user),
                            'default' => $alert ? $alert->pois->pluck('id')->all() : [],
                        ],
                    ];
                    break;
                default:
                    break;
            }
        }

        return $types;
    }

    public static function getTypes()
    {
        $types = [
            [
                'type'  => 'overspeed',
                'title' => trans('front.overspeed'),
            ],
            [
                'type'  => 'stop_duration',
                'title' => trans('front.stop_duration'),
            ],
            [
                'type'  => 'time_duration',
                'title' => trans('front.time_duration'),
            ],
            [
                'type'  => 'offline_duration',
                'title' => trans('front.offline_duration'),
            ],
            [
                'type' => 'ignition_duration',
                'title' => trans('front.ignition_duration'),
            ],
            [
                'type'  => 'idle_duration',
                'title' => trans('front.idle_duration'),
            ],
            [
                'type'  => 'move_start',
                'title' => trans('front.start_of_movement'),
            ],
            [
                'type'  => 'driver',
                'title' => trans('front.driver_change'),
            ],
            [
                'type'  => 'driver_unauthorized',
                'title' => trans('front.driver_change_authorization'),
            ],
            [
                'type'  => 'geofence_in',
                'title' => trans('front.geofence') . ' ' . trans('global.in'),
            ],
            [
                'type'  => 'geofence_out',
                'title' => trans('front.geofence') . ' ' . trans('global.out'),
            ],
            [
                'type'  => 'geofence_inout',
                'title' => trans('front.geofence') . ' ' . trans('global.in') . '/' . trans('global.out'),
            ],
            [
                'type'  => 'custom',
                'title' => trans('front.custom_events'),
            ],
            [
                'type'  => 'sos',
                'title' => 'SOS',
            ],
            [
                'type'  => 'fuel_change',
                'title' => trans('front.fuel') . ' (' . trans('front.fill_theft') . ')',
            ],
            [
                'type' => 'distance',
                'title' => trans('global.distance'),
            ],
            [
                'type'  => 'poi_stop_duration',
                'title' => trans('front.poi_stop_duration'),
            ],
            [
                'type'  => 'poi_idle_duration',
                'title' => trans('front.poi_idle_duration'),
            ],
        ];

        if (!config('addon.alert_time_duration'))
            $expect[] = 'time_duration';

        if (!empty($expect))
            $types = array_where($types, function ($type) use ($expect) {
                return !in_array($type['type'], $expect);
            });
            
        //reindex
        return array_values($types);
    }

    public function getSchedules($alert = null)
    {
        $weekdays = [
            'monday'    => trans('front.monday'),
            'tuesday'   => trans('front.tuesday'),
            'wednesday' => trans('front.wednesday'),
            'thursday'  => trans('front.thursday'),
            'friday'    => trans('front.friday'),
            'saturday'  => trans('front.saturday'),
            'sunday'    => trans('front.sunday')
        ];

        $schedules = [];

        foreach($weekdays as $weekday => $title)
        {
            $items = [];
            $actives = $alert ? array_get($alert->schedules, $weekday, []) : [];

            $times = getSelectTimeRange();

            foreach($times as $time => $displayTime)
            {
                $items[] = [
                    'id'     => $time,
                    'title'  => $displayTime,
                    'active' => $alert ? in_array($time, $actives) : false
                ];
            }

            $schedules[] = [
                    'id'    => $weekday,
                    'title' => $title,
                    'items' => $items,
            ];
        }

        $invert = $this->user ? (8 - $this->user->week_start_day) % 7 : 0;
        while ($invert-- > 0) {
            array_unshift($schedules, array_pop($schedules));
        }

        return $schedules;
    }

    public function getNotifications($alert = null)
    {
        $alertNotifications = $alert->notifications ?? [];

        $notifications = [
            [
                'active' => $alertNotifications ? array_get($alertNotifications, 'color.active', false) : false,
                'name' => 'color',
                'title' => trans('validation.attributes.color'),
                'input' => $alertNotifications ? array_get($alertNotifications, 'color.input') : null,
                'input_type' => 'color',
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "silent.active", false) : false,
                'name' => 'silent',
                'title' => trans('validation.attributes.silent_notification'),
                'input' => $alertNotifications ? array_get($alertNotifications, "silent.input", '0') : '0',
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "sound.active", true) : true,
                'name' => 'sound',
                'title' => trans('validation.attributes.sound_notification'),
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "auto_hide.active", true) : true,
                'name' => 'auto_hide',
                'title' => trans('validation.attributes.auto_hide_notification'),
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "push.active", true) : true,
                'name' => 'push',
                'title' => trans('validation.attributes.push_notification'),
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "email.active", false) : false,
                'name' => 'email',
                'title' => trans('validation.attributes.email_notification'),
                'input' => $alertNotifications ? array_get($alertNotifications, "email.input", '') : '',
                'description' => trans('front.email_semicolon')
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "sms.active", false) : false,
                'name' => 'sms',
                'title' => trans('validation.attributes.sms_notification'),
                'input' => $alertNotifications ? array_get($alertNotifications, "sms.input", '') : '',
                'description' => trans('front.sms_semicolon')
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "webhook.active", false) : false,
                'name' => 'webhook',
                'title' => trans('validation.attributes.webhook_notification'),
                'input' => $alertNotifications ? array_get($alertNotifications, "webhook.input", '') : '',
                'description' => trans('front.webhook')
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "sharing_email.active", false) : false,
                'name' => 'sharing_email',
                'title' => trans('validation.attributes.sharing_email'),
                'input' => $alertNotifications ? array_get($alertNotifications, "sharing_email.input", '') : '',
                'description' => trans('front.email_semicolon'),
            ],
            [
                'active' => $alertNotifications ? array_get($alertNotifications, "sharing_sms.active", false) : false,
                'name' => 'sharing_sms',
                'title' => trans('validation.attributes.sharing_sms'),
                'input' => $alertNotifications ? array_get($alertNotifications, "sharing_sms.input", '') : '',
                'description' => trans('front.sms_semicolon'),
            ],
        ];

        $notifications = array_where($notifications, function($notification) {
            if (in_array($notification['name'], ['sms', 'sharing_sms']) && ! auth()->user()->canSendSMS()) {
                return false;
            }

            if (in_array($notification['name'], ['sharing_email', 'sharing_sms'])
                && (! settings('plugins.alert_sharing.status')
                || ! auth()->user()->can('create', new \Tobuli\Entities\Sharing()))
            ) {
                return false;
            }

            return true;
        });

        // indexes reset with array_values
        return array_values($notifications);
    }

    public function getCommands()
    {
        AlertFormValidator::validate('commands', $this->data);

        $devices = DeviceRepo::getWhereIn($this->data['devices']);

        $commands = SendCommandModalHelper::getCommands($devices);
/*
        foreach ($commands as &$command) {
            if (empty($command['attributes']))
                continue;

            foreach($command['attributes'] as &$attribute) {
                $attribute['name'] = 'command[' .$attribute['name'] . ']';
            }
        }
*/
        return $commands;
    }

    public function syncDevices()
    {
        $alert = AlertRepo::findWithAttributes($this->data['alert_id']);

        $this->checkException('alerts', 'update', $alert);

        AlertFormValidator::validate('devices', $this->data);

        $alert->devices()->sync(array_get($this->data, 'devices', []));

        return ['status' => 1];
    }

    public function summary($from = null, $to = null)
    {
        $query = $this->user->alerts()
            ->select(DB::raw('count(*) as count, alerts.type'))
            ->join('events', 'alerts.id', '=', 'events.alert_id')
            ->groupBy('alerts.type');

        if ($from)
            $query->where('events.created_at', '>=', $from);

        if ($to)
            $query->where('events.created_at', '<=', $to);

        $alerts = $query->get()->pluck('count', 'type');

        $types = collect(AlertModalHelper::getTypes())
            ->map(function($type) use ($alerts) {
                $type['count'] = $alerts[$type['type']] ?? 0;

                return $type;
            });

        return $types;
    }
}