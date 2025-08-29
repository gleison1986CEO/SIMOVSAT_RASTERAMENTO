<?php

namespace App\Jobs;

use App\Console\ProcessManager;
use Carbon\Carbon;
use Exception;
use Formatter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tobuli\Entities\Device;
use Tobuli\Entities\Event;
use Tobuli\Entities\SendQueue;
use Tobuli\Entities\User;

class ConfirmFuelLevelChange extends Job implements ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    const SECONDS_GAP = 60;
    const SECONDS_MAX_DURATION = 3600;
    const DIFF_MIN_PERCENT = 3;

    private $eventData;
    private $initTime;
    private $iteration;
    private $mainFuel;
    private $idleDuration;
    private $device;
    private $processManager;
    private $processKey;
    private $maxIdleDuration;

    public function __construct(
        $processKey,
        $time,
        array $eventData,
        array $mainFuel,
        int $iteration = 1,
        int $idleDuration = 0
    ) {
        $this->processKey = $processKey;
        $this->device = Device::find($eventData['device_id']);
        $this->initTime = $time;
        $this->eventData = $eventData;
        $this->mainFuel = $mainFuel;
        $this->iteration = $iteration;
        $this->idleDuration = $idleDuration;

        $this->processManager = self::generateProcessManager();
        $this->maxIdleDuration = settings('main_settings.default_object_online_timeout') * 60;
    }

    public static function generateProcessManager(): ProcessManager
    {
        $manager = new ProcessManager('ConfirmFuelLevelChange', ConfirmFuelLevelChange::SECONDS_GAP * 2);
        $manager->disableUnlocking();

        return $manager;
    }

    /**
     *             /    | same as 2
     *       -----      | 3. if sensor sent no positions in this period - prolong check; else - finalize
     *     /            | same as 2
     *   /              | 2. if change is larger than X percent - continue, else - finalize
     * /                | 1. if change is larger than X percent - save initial stats and continue, else - finalize
     * @return bool
     */
    public function handle()
    {
        $processDuration = self::SECONDS_GAP * $this->iteration;

        if ($processDuration > self::SECONDS_MAX_DURATION || $this->idleDuration > $this->maxIdleDuration) {
            return $this->finalize();
        }

        try {
            $diff = $this->getFuelDifference();
        } catch (Exception $e) {
            $diff = null;
        }

        if ($diff && $diff['increased'] !== $this->mainFuel['increased']) {
            return $this->finalize();
        }

        if ($diff && abs($diff['percent']) < self::DIFF_MIN_PERCENT) {
            return $this->finalize();
        }

        return $this->prolongCheck($diff);
    }

    private function finalize(): bool
    {
        $this->processManager->unlock($this->processKey);

        $totalDifference = $this->mainFuel['last_value'] - $this->mainFuel['first_value'];
        $minDifferance = $totalDifference > 0 ? $this->device->min_fuel_fillings : $this->device->min_fuel_thefts;

        if (abs($totalDifference) < $minDifferance) {
            return false;
        }

        if (!($user = User::find($this->eventData['user_id']))){
            return false;
        }

        Formatter::byUser($user);

        $this->eventData = array_merge($this->eventData, [
            'type'       => $totalDifference > 0 ? Event::TYPE_FUEL_FILL : Event::TYPE_FUEL_THEFT,
            'message'    => $totalDifference > 0 ? trans('front.fuel_fillings') : trans('front.fuel_thefts'),
            'additional' => [
                'difference' => round(abs($totalDifference))
            ]
        ]);

        $event = Event::create($this->eventData);

        SendQueue::create([
            'user_id'   => $event->user_id,
            'type'      => $event->type,
            'data'      => $event,
            'channels'  => $this->eventData['channels']
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    private function getFuelDifference()
    {
        $timeFrom = Carbon::parse($this->initTime)->addSeconds(self::SECONDS_GAP * ($this->iteration - 1));
        $timeTo   = Carbon::parse($this->initTime)->addSeconds(self::SECONDS_GAP * $this->iteration);

        if ($this->idleDuration) {
            $timeFrom->subSeconds($this->idleDuration);
        }

        $positions = $this->device->positions()
            ->where('time', '>', $timeFrom)
            ->where('time', '<=', $timeTo)
            ->orderBy('time', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($positions->count() < 1) {
            return null;
        }

        return getFuelDifference($this->device, $positions, $this->mainFuel['last_value'] ?? null);
    }

    private function prolongCheck($diff): bool
    {
        if (empty($diff)) {
            $this->idleDuration += self::SECONDS_GAP;
        } else {
            $this->idleDuration = 0;
            $this->mainFuel['last_value'] = $diff['last_value'];
        }

        dispatch(
            (new ConfirmFuelLevelChange(
                $this->processKey,
                $this->initTime,
                $this->eventData,
                $this->mainFuel,
                ++$this->iteration,
                $this->idleDuration
            ))->delay(ConfirmFuelLevelChange::SECONDS_GAP)
        );

        return $this->processManager->prolongLock($this->processKey);
    }
}