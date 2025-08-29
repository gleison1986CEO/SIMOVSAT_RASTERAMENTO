<?php namespace Tobuli\Helpers\Dashboard\Blocks;

class DeviceStatusCountsBlock extends Block
{
    protected function getName()
    {
        return 'device_status_counts';
    }

    protected function getContent()
    {
        return [
            'devices'         => $this->user->devices()->count(),
            'online'          => $this->user->devices()->online()->count(),
            'offline'         => $this->user->devices()->offline()->count(),
            'never_connected' => $this->user->devices()->neverConnected()->count(),
            'expired'         => $this->user->devices()->expired()->count(),
        ];
    }
}