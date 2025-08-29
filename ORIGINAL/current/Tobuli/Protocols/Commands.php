<?php

namespace Tobuli\Protocols;


class Commands
{
    const TYPE_CUSTOM = 'custom';
    const TYPE_IDENTIFICATION = "deviceIdentification";
    const TYPE_POSITION_SINGLE = "positionSingle";
    const TYPE_POSITION_PERIODIC = "positionPeriodic";
    const TYPE_POSITION_LOG = "positionLog";
    const TYPE_POSITION_STOP = "positionStop";
    const TYPE_ENGINE_STOP = "engineStop";
    const TYPE_ENGINE_RESUME = "engineResume";
    const TYPE_ALARM_ARM = "alarmArm";
    const TYPE_ALARM_DISARM = "alarmDisarm";
    const TYPE_SET_TIMEZONE = "setTimezone";
    const TYPE_REQUEST_PHOTO = "requestPhoto";
    const TYPE_REBOOT_DEVICE = "rebootDevice";
    const TYPE_SEND_SMS = "sendSms";
    const TYPE_SEND_USSD = "sendUssd";
    const TYPE_SOS_NUMBER = "sosNumber";
    const TYPE_SILENCE_TIME = "silenceTime";
    const TYPE_SET_PHONEBOOK = "setPhonebook";
    const TYPE_VOICE_MESSAGE = "voiceMessage";
    const TYPE_OUTPUT_CONTROL = "outputControl";
    const TYPE_VOICE_MONITORING = "voiceMonitoring";
    const TYPE_SET_AGPS = "setAgps";
    const TYPE_SET_INDICATOR = "setIndicator";
    const TYPE_CONFIGURATION = "configuration";
    const TYPE_GET_VERSION = "getVersion";
    const TYPE_FIRMWARE_UPDATE = "firmwareUpdate";
    const TYPE_SET_CONNECTION = "setConnection";
    const TYPE_SET_ODOMETER = "setOdometer";

    const TYPE_DOOR_OPEN = "doorOpen";
    const TYPE_DOOR_CLOSE = "doorClose";
    const TYPE_TEMPLATE = "template";
    
    const TYPE_MODE_POWER_SAVING = "modePowerSaving";
    const TYPE_MODE_DEEP_SLEEP = "modeDeepSleep";
    
    const TYPE_ALARM_GEOFENCE = "movementAlarm";
    const TYPE_ALARM_BATTERY = "alarmBattery";
    const TYPE_ALARM_SOS = "alarmSos";
    const TYPE_ALARM_REMOVE = "alarmRemove";
    const TYPE_ALARM_CLOCK = "alarmClock";
    const TYPE_ALARM_SPEED = "alarmSpeed";
    const TYPE_ALARM_FALL = "alarmFall";
    const TYPE_ALARM_VIBRATION = "alarmVibration";
    
    const KEY_UNIQUE_ID = "uniqueId";
    const KEY_FREQUENCY = "frequency";
    const KEY_TIMEZONE = "timezone";
    const KEY_DEVICE_PASSWORD = "devicePassword";
    const KEY_RADIUS = "radius";
    const KEY_MESSAGE = "message";
    const KEY_ENABLE = "enable";
    const KEY_DATA = "data";
    const KEY_INDEX = "index";
    const KEY_PHONE = "phone";
    const KEY_SERVER = "server";
    const KEY_PORT = "port";

    const KEY_UNIT = "unit";
    const KEY_TYPE = "type";

    const CONNECTION_GPRS = 'gprs';
    const CONNECTION_SMS = 'sms';

    protected $commands = [];

    public function __construct()
    {
        $this->commands = [
            self::TYPE_CUSTOM => [
                'type' => self::TYPE_CUSTOM,
                'title' => trans('front.custom_command'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.message'),
                        'name'  => self::KEY_DATA,
                        'type'  => 'text',
                        'validation' => 'required',
                        'description' => trans('front.raw_command_supports') .'<br><br>'. trans('front.gprs_template_variables')
                    ]
                ]
            ],
            self::TYPE_GET_VERSION => [
                'type' => self::TYPE_GET_VERSION,
                'title' => trans('front.get_version')
            ],
            self::TYPE_POSITION_SINGLE => [
                'type' => self::TYPE_POSITION_SINGLE,
                'title' => trans('front.position_single'),
            ],
            self::TYPE_POSITION_STOP => [
                'type' => self::TYPE_POSITION_STOP,
                'title' => trans('front.stop_reporting'),
            ],
            self::TYPE_POSITION_PERIODIC => [
                'type' => self::TYPE_POSITION_PERIODIC,
                'title' => trans('front.periodic_reporting'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.unit'),
                        'name'  => self::KEY_UNIT,
                        'type'  => 'select',
                        'options' => [
                            [
                                'id' => 'second',
                                'title' => trans('front.second')
                            ],
                            [
                                'id' => 'minute',
                                'title' => trans('front.minute')
                            ],
                            [
                                'id' => 'hour',
                                'title' => trans('front.hour')
                            ]
                        ],
                        'validation' => 'required|in:second,minute,hour',
                        'default' => 'minute'
                    ],
                    [
                        'title' => trans('validation.attributes.frequency'),
                        'name'  => self::KEY_FREQUENCY,
                        'type'  => 'integer',
                        'default' => 1,
                        'validation' => 'required|numeric'
                    ]
                ]
            ],
            self::TYPE_POSITION_LOG => [
                'type' => self::TYPE_POSITION_LOG,
                'title' => trans('front.setting_log_interval'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.unit'),
                        'name'  => self::KEY_UNIT,
                        'type'  => 'select',
                        'options' => [
                            [
                                'id' => 'second',
                                'title' => trans('front.second')
                            ],
                            [
                                'id' => 'minute',
                                'title' => trans('front.minute')
                            ],
                            [
                                'id' => 'hour',
                                'title' => trans('front.hour')
                            ]
                        ],
                        'validation' => 'required|in:second,minute,hour',
                        'default' => 'minute'
                    ],
                    [
                        'title' => trans('validation.attributes.frequency'),
                        'name'  => self::KEY_FREQUENCY,
                        'type'  => 'integer',
                        'default' => 1,
                        'validation' => 'required|numeric'
                    ]
                ]
            ],
            self::TYPE_OUTPUT_CONTROL => [
                'type' => self::TYPE_OUTPUT_CONTROL,
                'title' => trans('front.output_control'),
                'attributes' => [
                    [
                        'title' => 'Index',
                        'name'  => self::KEY_INDEX,
                        'type'  => 'string',
                        'validation' => 'required'
                    ],
                    [
                        'title' => 'Data',
                        'name'  => self::KEY_DATA,
                        'type'  => 'string',
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_SET_TIMEZONE => [
                'type' => self::TYPE_SET_TIMEZONE,
                'title' => trans('front.set_timezone'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.parameter'),
                        'name'  => self::KEY_TIMEZONE,
                        'type'  => 'select',
                        'options' => [
                            [
                                'id' => 'GMT-11:00',
                                'title' => 'GMT -11:00'
                            ],
                            [
                                'id' => 'GMT-10:00',
                                'title' => 'GMT -10:00'
                            ],
                            [
                                'id' => 'GMT-9:00',
                                'title' => 'GMT -9:00'
                            ],
                            [
                                'id' => 'GMT-8:00',
                                'title' => 'GMT -8:00'
                            ],
                            [
                                'id' => 'GMT-7:00',
                                'title' => 'GMT -7:00'
                            ],
                            [
                                'id' => 'GMT-6:00',
                                'title' => 'GMT -6:00'
                            ],
                            [
                                'id' => 'GMT-5:00',
                                'title' => 'GMT -5:00'
                            ],
                            [
                                'id' => 'GMT-4:00',
                                'title' => 'GMT -4:00'
                            ],
                            [
                                'id' => 'GMT-3:00',
                                'title' => 'GMT -3:00'
                            ],
                            [
                                'id' => 'GMT-2:00',
                                'title' => 'GMT -2:00'
                            ],
                            [
                                'id' => 'GMT-1:00',
                                'title' => 'GMT -1:00'
                            ],
                            [
                                'id' => 'GMT',
                                'title' => 'GMT'
                            ],
                            [
                                'id' => 'GMT+1:00',
                                'title' => 'GMT +1:00'
                            ],
                            [
                                'id' => 'GMT+2:00',
                                'title' => 'GMT +2:00'
                            ],
                            [
                                'id' => 'GMT+3:00',
                                'title' => 'GMT +3:00'
                            ],
                            [
                                'id' => 'GMT+4:00',
                                'title' => 'GMT +4:00'
                            ],
                            [
                                'id' => 'GMT+5:00',
                                'title' => 'GMT +5:00'
                            ],
                            [
                                'id' => 'GMT+6:00',
                                'title' => 'GMT +6:00'
                            ],
                            [
                                'id' => 'GMT+7:00',
                                'title' => 'GMT +7:00'
                            ],
                            [
                                'id' => 'GMT+8:00',
                                'title' => 'GMT +8:00'
                            ],
                            [
                                'id' => 'GMT+9:00',
                                'title' => 'GMT +9:00'
                            ],
                            [
                                'id' => 'GMT+10:00',
                                'title' => 'GMT +10:00'
                            ],
                            [
                                'id' => 'GMT+11:00',
                                'title' => 'GMT +11:00'
                            ],
                        ],
                        'default' => 'GMT',
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_ALARM_SPEED => [
                'type' => self::TYPE_ALARM_SPEED,
                'title' => 'TYPE_ALARM_SPEED',
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.parameter'),
                        'name'  => self::KEY_DATA,
                        'type'  => 'string',
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_ALARM_SOS => [
                'type' => self::TYPE_ALARM_SOS,
                'title' => trans('front.sos_message_alarm'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.parameter'),
                        'name'  => self::KEY_ENABLE,
                        'type'  => 'select',
                        'options' => [
                            [
                                'id' => 0,
                                'title' => trans('front.off')
                            ],
                            [
                                'id' => 1,
                                'title' => trans('front.on')
                            ]
                        ],
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_ALARM_BATTERY => [
                'type' => self::TYPE_ALARM_BATTERY,
                'title' => trans('front.low_battery_alarm'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.parameter'),
                        'name'  => self::KEY_ENABLE,
                        'type'  => 'select',
                        'options' => [
                            [
                                'id' => 0,
                                'title' => trans('front.off')
                            ],
                            [
                                'id' => 1,
                                'title' => trans('front.on')
                            ]
                        ],
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_ALARM_REMOVE => [
                'type' => self::TYPE_ALARM_REMOVE,
                'title' => trans('front.alarm_of_taking_watch'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.parameter'),
                        'name'  => self::KEY_ENABLE,
                        'type'  => 'select',
                        'options' => [
                            [
                                'id' => 0,
                                'title' => trans('front.off')
                            ],
                            [
                                'id' => 1,
                                'title' => trans('front.on')
                            ]
                        ],
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_ALARM_CLOCK => [
                'type' => self::TYPE_ALARM_CLOCK,
                'title' => trans('front.alarm_clock_setting_order'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.parameter'),
                        'name'  => self::KEY_DATA,
                        'type'  => 'string',
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_ALARM_ARM => [
                'type' => self::TYPE_ALARM_ARM,
                'title' => trans('front.alarm_arm'),
            ],
            self::TYPE_ALARM_DISARM => [
                'type' => self::TYPE_ALARM_DISARM,
                'title' => trans('front.alarm_disarm'),
            ],
            self::TYPE_ALARM_GEOFENCE => [
                'type' => self::TYPE_ALARM_GEOFENCE,
                'title' => trans('front.movement_alarm'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.parameter'),
                        'name'  => self::KEY_RADIUS,
                        'type'  => 'integer',
                        'validation' => 'required|integer'
                    ]
                ]
            ],
            self::TYPE_REQUEST_PHOTO => [
                'type' => self::TYPE_REQUEST_PHOTO,
                'title' => trans('front.request_photo')
            ],
            self::TYPE_ENGINE_STOP => [
                'type' => self::TYPE_ENGINE_STOP,
                'title' => trans('front.engine_stop')
            ],
            self::TYPE_ENGINE_RESUME => [
                'type' => self::TYPE_ENGINE_RESUME,
                'title' => trans('front.engine_resume')
            ],
            self::TYPE_REBOOT_DEVICE => [
                'type' => self::TYPE_REBOOT_DEVICE,
                'title' => trans('front.reboot_device')
            ],
            self::TYPE_DOOR_OPEN => [
                'type' => self::TYPE_DOOR_OPEN,
                'title' => trans('front.door_open')
            ],
            self::TYPE_DOOR_CLOSE => [
                'type' => self::TYPE_DOOR_CLOSE,
                'title' => trans('front.door_close')
            ],
            self::TYPE_SEND_SMS => [
                'type' => self::TYPE_SEND_SMS,
                'title' => trans('front.send_sms'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.sim_number'),
                        'name'  => self::KEY_PHONE,
                        'type'  => 'string',
                        'validation' => 'required'
                    ],
                    [
                        'title' => trans('validation.attributes.message'),
                        'name'  => self::KEY_MESSAGE,
                        'type'  => 'text',
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_SOS_NUMBER => [
                'type' => self::TYPE_SOS_NUMBER,
                'title' => trans('front.sos_number_setting'),
                'attributes' => [
                    [
                        'title' => self::KEY_INDEX,
                        'name'  => self::KEY_INDEX,
                        'type'  => 'select',
                        'options' => [
                            [
                                'id' => 1,
                                'title' => trans('front.first')
                            ],
                            [
                                'id' => 2,
                                'title' => trans('front.second')
                            ],
                            [
                                'id' => 3,
                                'title' => trans('front.third')
                            ]
                        ],
                        'default' => 1,
                        'validation' => 'required'
                    ],
                    [
                        'title' => trans('validation.attributes.sim_number'),
                        'name'  => self::KEY_PHONE,
                        'type'  => 'string',
                        'validation' => 'required'
                    ]
                ]
            ],
            self::TYPE_SILENCE_TIME => [
                'type' => self::TYPE_SILENCE_TIME,
                'title' => trans('front.time_interval_setting_of_silencetime'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.parameter'),
                        'name'  => self::KEY_DATA,
                        'type'  => 'string',
                        'validation' => 'required'
                    ],
                ]
            ],
            self::TYPE_SET_PHONEBOOK => [
                'type' => self::TYPE_SET_PHONEBOOK,
                'title' => trans('front.phone_book_setting_order'),
                'attributes' => [
                    [
                        'title' => trans('validation.attributes.name') . ' 1',
                        'name'  => 'name[0]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.sim_number') . ' 1',
                        'name'  => 'phone[0]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.name') . ' 2',
                        'name'  => 'name[1]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.sim_number') . ' 2',
                        'name'  => 'phone[1]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.name') . ' 3',
                        'name'  => 'name[2]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.sim_number') . ' 3',
                        'name'  => 'phone[2]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.name') . ' 4',
                        'name'  => 'name[3]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.sim_number') . ' 4',
                        'name'  => 'phone[3]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.name') . ' 5',
                        'name'  => 'name[4]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                    [
                        'title' => trans('validation.attributes.sim_number') . ' 5',
                        'name'  => 'phone[4]',
                        'type'  => 'string',
                        'validation' => ''
                    ],
                ]
            ],

            self::TYPE_TEMPLATE => [
                'type' => self::TYPE_TEMPLATE,
                'title' => 'Template',
                'attributes' => []
            ]
        ];
    }

    public function get($type, $attributes = [])
    {
        $command = $this->commands[$type];

        if ($attributes) {
            foreach ($attributes as $attribute)
                $command['attributes'][] = $attribute;
        }

        return $command;
    }

    public function all()
    {
        return $this->commands;
    }

    public function only($only)
    {
        return array_only($this->commands, $only);
    }

    public static function validationRules($type, $commands)
    {
        $rules = [];

        foreach ($commands as $command)
        {
            if ($command['type'] != $type)
                continue;

            if (empty($command['attributes']))
                continue;

            foreach ($command['attributes'] as $attribute)
            {
                if (empty($attribute['validation']))
                    continue;

                $rules[$attribute['name']] = $attribute['validation'];
            }
        }

        return $rules;
    }
}