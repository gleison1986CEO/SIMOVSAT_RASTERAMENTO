<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Tobuli\Entities\Device;

class DeviceExpirationEvent extends Event
{
    use SerializesModels;

    public $device;

    public function __construct(Device $device) {
        $this->device = $device;
    }
}
