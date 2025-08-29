<?php namespace Tobuli\Helpers\Alerts;

use App\Console\ProcessManager;
use App\Jobs\ConfirmFuelLevelChange;
use Tobuli\Entities\Alert;
use Tobuli\Entities\Device;
use Tobuli\Entities\Event;
use Tobuli\Entities\TraccarPosition;

class FuelLevelChangeCheck extends AlertCheck
{
    const PERCENTAGE_CHANGE_THRESHOLD = 5;

    private $processManager;

    public function __construct(Device $device, Alert $alert)
    {
        parent::__construct($device, $alert);

        $this->processManager = ConfirmFuelLevelChange::generateProcessManager();
    }

    /**
     * @param TraccarPosition $position
     * @param TraccarPosition $prevPosition
     * @return array|null
     */

    public function checkEvents($position, $prevPosition)
    {
        $processKey = "{$this->alert->id}_{$this->device->id}";

        if ( ! $this->processManager->lock($processKey))
            return null;

        $fail = function () use ($processKey) {
            $this->processManager->unlock($processKey);
            return null;
        };

        if ( ! $this->check($position))
            return $fail();

        try {
            $change = getFuelDifference($this->device, [$prevPosition, $position]);
        } catch (\Exception $exception) {
            return $fail();
        }

        $percent = $change['percent'];
        
        if (abs($percent) < self::PERCENTAGE_CHANGE_THRESHOLD)
            return $fail();

        $event = $this->getEvent();
        $event->type = $percent > 0 ? Event::TYPE_FUEL_FILL : Event::TYPE_FUEL_THEFT;

        dispatch(
            (new ConfirmFuelLevelChange($processKey, $position->time, $event->toArray(), $change))
                ->delay(ConfirmFuelLevelChange::SECONDS_GAP)
        );

        return [];
    }

    /**
     * @param TraccarPosition $position
     * @return bool|null
     */

    private function check($position)
    {
        if ( ! $position)
            return false;

        if ( ! $position->isValid())
            return null;

        if ( ! $this->checkAlertPosition($position))
            return false;

        return true;
    }
}