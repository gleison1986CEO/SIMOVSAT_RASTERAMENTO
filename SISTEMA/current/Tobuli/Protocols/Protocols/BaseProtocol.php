<?php

namespace Tobuli\Protocols\Protocols;

use Tobuli\Entities\Device;
use Tobuli\Entities\UserGprsTemplate;
use Tobuli\Protocols\Protocol;
use Tobuli\Protocols\Commands;
use CustomFacades\Repositories\UserGprsTemplateRepo;

class BaseProtocol implements Protocol
{
    protected $passwordRequired = false;

    protected $commandsManager;

    protected function commands()
    {
        return [
            $this->initCommand(Commands::TYPE_CUSTOM)
        ];
    }

    public function getCommands()
    {
        $commands = $this->commands();

        if ($this->passwordRequired) {
            $commands = $this->appendPasswordAttribute($commands);
        }

        return $commands;
    }

    public function getTemplateCommands($templates, $display = true)
    {
        $commands = [];

        if ( ! $templates)
            return $commands;

        foreach ($templates as $template) {
            $commands[] = $this->initTemplateCommand($template, $display);
        }

        return $commands;
    }

    public function initTemplateCommand(UserGprsTemplate $template, $display)
    {
        $command = [
            'type'  => 'template_' . $template->id,
            'title' => $template->title . '(' . trans('validation.attributes.gprs_template_id') . ')',
        ];

        if ($display)
            $command['attributes'] = [
                [
                    'title'       => trans('validation.attributes.message'),
                    'name'        => Commands::KEY_DATA,
                    'type'        => 'text',
                    'description' => trans('front.raw_command_supports') .'<br><br>'. trans('front.gprs_template_variables'),
                    'default'     => $template->message
                ]
            ];

        return $command;
    }

    protected function initCommand($type, $attributes = [])
    {
        if ( ! $this->commandsManager)
            $this->commandsManager = new Commands();

        return $this->commandsManager->get($type, $attributes);
    }

    protected function appendPasswordAttribute($commands)
    {
        foreach ($commands as &$command)
        {
            $attributes = empty($command['attributes']) ? [] : $command['attributes'];

            $attributes[] = [
                'title' => trans('validation.attributes.password'),
                'name'  => Commands::KEY_DEVICE_PASSWORD,
                'type'  => 'string',
                'validation' => ''
            ];

            $command['attributes'] = $attributes;
        }

        return $commands;
    }

    public function validationRules($type, $commands)
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

    public function buildCommand($device, $data)
    {
        $data = $this->_buildCommand($device, $data);

        $attributes = array_only($data, [
            Commands::KEY_FREQUENCY,
            Commands::KEY_TIMEZONE,
            Commands::KEY_DEVICE_PASSWORD,
            Commands::KEY_RADIUS,
            Commands::KEY_MESSAGE,
            Commands::KEY_ENABLE,
            Commands::KEY_DATA,
            Commands::KEY_INDEX,
            Commands::KEY_PHONE,
            Commands::KEY_SERVER,
            Commands::KEY_PORT
        ]);

        foreach ($attributes as $key => $value) {
            switch ($key) {
                case Commands::KEY_FREQUENCY:
                case Commands::KEY_RADIUS:
                case Commands::KEY_INDEX:
                case Commands::KEY_PORT:
                    $attributes[$key] = (int)$value;
                    break;

                case Commands::KEY_ENABLE:
                    $attributes[$key] = (boolean)$value;
                    break;
            }
        }

        $data = [
            'uniqueId' => $device->imei,
            'type' => $data['type'],
        ];

        if ( ! empty($attributes))
            $data['attributes'] = $attributes;

        return $data;
    }

    protected function _buildCommand($device, $data)
    {
        if (starts_with($data['type'], 'template_'))
            list($data['type'], $data['gprs_template_id']) = explode('_', $data['type']);

        $method = 'buildCommand' . ucfirst($data['type']);

        if (method_exists($this,$method))
            $data = call_user_func([$this,$method], $device, $data);

        return $data;
    }

    protected function buildCommandPositionPeriodic($device, $data)
    {
        if (empty($data['unit']))
            return $data;

        switch ($data['unit'])
        {
            case 'minute':
                $data['frequency'] *= 60;
                break;
            case 'hour':
                $data['frequency'] *= 3600;
                break;
        }

        return $data;
    }

    protected function buildCommandPositionLog($device, $data)
    {
        if (empty($data['unit']))
            return $data;

        switch ($data['unit'])
        {
            case 'minute':
                $data['frequency'] *= 60;
                break;
            case 'hour':
                $data['frequency'] *= 3600;
                break;
        }

        return $data;
    }

    protected function buildCommandCustom($device, $data)
    {
        $imei = $device->imei;

        if ($device->protocol == 'tk103') {
            $imei = '0' . substr($imei, -11);
        }

        $command = strtr($data[Commands::KEY_DATA], [
            '[%IMEI%]' => $imei
        ]);

        $data[Commands::KEY_DATA] = $command;

        return $data;
    }

    protected function buildCommandTemplate($device, $data)
    {
        $where = ['id' => $data['gprs_template_id']];

        if (auth()->user())
            $where['user_id'] = auth()->user()->id;

        $grps_template = UserGprsTemplateRepo::findWhere($where);

        $message = $grps_template ? $grps_template->message : '';

        if ( ! $device->gprs_templates_only && isset($data[Commands::KEY_DATA]))
            $message = $data[Commands::KEY_DATA];

        $data[Commands::KEY_TYPE] = Commands::TYPE_CUSTOM;
        $data[Commands::KEY_DATA] = $message;

        return $this->buildCommandCustom($device, $data);
    }
}