<?php namespace App\Console\Commands;

use App\Events\DeviceSubscriptionExpire;
use Illuminate\Console\Command;
use Tobuli\Entities\Device;
use Tobuli\Entities\Event;
use Tobuli\Entities\SendQueue;
use Carbon\Carbon;

class CheckDevicesExpirationCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'devices_expiration:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates devices expiration events.';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (settings('main_settings.expire_notification.active_before')) {
            $days_before = settings('main_settings.expire_notification.days_before');

            $expiring = Device::with('users')
                ->isExpiringAfter($days_before)
                ->whereDoesntHave('eventsLog', function ($query) use ($days_before) {
                    $query
                        ->where('type', Event::TYPE_EXPIRING_DEVICE)
                        ->whereRaw('`events_log`.`time` <= `devices`.`expiration_date`')
                        ->whereRaw('`events_log`.`time` >= DATE_SUB(`devices`.`expiration_date`, INTERVAL ' . $days_before . ' DAY)');
                })->get();

            $this->createEvents(Event::TYPE_EXPIRING_DEVICE, $expiring);
        }

        if (settings('main_settings.expire_notification.active_after')) {
            $days_after = settings('main_settings.expire_notification.days_after');

            $expired = Device::with('users')
                ->isExpiredBefore($days_after)
                ->whereDoesntHave('eventsLog', function ($query) {
                    $query
                        ->where('type', Event::TYPE_EXPIRED_DEVICE)
                        ->whereRaw('`events_log`.`time` >= `devices`.`expiration_date`');
                })->get();

            $this->createEvents(Event::TYPE_EXPIRED_DEVICE, $expired);
        }

        $expired = Device::with('users')
            ->expiredForLastDays(7)
            ->whereDoesntHave('eventsLog', function ($query) {
                $query
                    ->where('type', Event::TYPE_DEVICE_SUBSCRIPTION_EXPIRED)
                    ->whereRaw('`events_log`.`time` >= `devices`.`expiration_date`');
            })->get();

        $this->dispatchEvents($expired, Event::TYPE_DEVICE_SUBSCRIPTION_EXPIRED);

        echo "DONE\n";
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    private function createEvents($type, $devices)
    {
        foreach ($devices as $device) {
            foreach ($device->users as $user) {
                $event = $device->events()->create([
                    'type'        => $type,
                    'message'     => $type,
                    'user_id'     => $user->id,
                    'device_id'   => $device->id,
                    'geofence_id' => null,
                    'altitude'    => $device->altitude,
                    'course'      => $device->course,
                    'latitude'    => $device->latitude,
                    'longitude'   => $device->longitude,
                    'speed'       => $device->speed,
                    'time'        => date('Y-m-d H:i:s'),
                ]);

                SendQueue::create([
                    'user_id'   => $user->id,
                    'type'      => $type,
                    'data'      => $event,
                    'channels'  => [
                        'push'  => true,
                        'email' => empty($user->manager->email) ? [$user->email] : [$user->email, $user->manager->email],
                        'mobile_number' => $user->mobile_number,
                    ]
                ]);
            }

            $device->logEvent($type);
        }
    }

    private function dispatchEvents($devices, $type)
    {
        foreach ($devices as $device) {
            $device->logEvent($type);

            event(new DeviceSubscriptionExpire($device));
        }
    }
}