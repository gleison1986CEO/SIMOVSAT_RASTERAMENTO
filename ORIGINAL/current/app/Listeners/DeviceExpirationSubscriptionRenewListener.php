<?php

namespace App\Listeners;

use App\Events\DeviceExpirationEvent;
use Tobuli\Services\SimBlockingService;

class DeviceExpirationSubscriptionRenewListener
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
            $this->blockingService->unblock($event->device);
        } catch (\Exception $e) {}
    }
}
