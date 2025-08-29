<?php

namespace Tobuli\Protocols\Protocols;

use Tobuli\Protocols\Protocol;
use Tobuli\Protocols\Commands;

class MeitrackProtocol extends BaseProtocol implements Protocol
{
    protected function commands()
    {
        return [
            $this->initCommand(Commands::TYPE_ENGINE_STOP),
            $this->initCommand(Commands::TYPE_ENGINE_RESUME),
            $this->initCommand(Commands::TYPE_ALARM_ARM),
            $this->initCommand(Commands::TYPE_ALARM_DISARM),
            $this->initCommand(Commands::TYPE_POSITION_SINGLE),
            $this->initCommand(Commands::TYPE_POSITION_LOG),
            $this->initCommand(Commands::TYPE_REQUEST_PHOTO, [
                [
                    'title' => Commands::KEY_INDEX,
                    'name'  => Commands::KEY_INDEX,
                    'type'  => 'select',
                    'options' => [
                        [
                            'id' => 1,
                            'title' => '1'
                        ],
                        [
                            'id' => 2,
                            'title' => '2'
                        ],
                        [
                            'id' => 3,
                            'title' => '3'
                        ],
                        [
                            'id' => 4,
                            'title' => '4'
                        ]
                    ],
                    'default' => 1,
                    'validation' => 'required'
                ],
            ]),
            $this->initCommand(Commands::TYPE_SEND_SMS),
            $this->initCommand(Commands::TYPE_CUSTOM)
        ];
    }
}