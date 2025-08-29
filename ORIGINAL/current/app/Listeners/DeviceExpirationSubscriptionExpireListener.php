<?php

namespace App\Listeners;

use App\Events\DeviceExpirationEvent;
use Tobuli\Services\SimBlockingService;

class DeviceExpirationSubscriptionExpireListener
{
    private $blockingService;

    public function __construct()
    {
        $this->blockingService = new SimBlockingService();
    }

    public function handle(DeviceExpirationEvent $event)
    {
        if (! settings('plugins.sim_blocking.status')) {
            return;
        }

        try {
            $this->blockingService->block($event->device);
        } catch (\Exception $e) {}
    }
}
