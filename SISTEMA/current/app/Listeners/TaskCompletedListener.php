<?php

namespace App\Listeners;

use App\Events\TaskStatusChange;
use Carbon\Carbon;
use Tobuli\Entities\Event;
use Tobuli\Entities\SendQueue;
use Tobuli\Entities\TaskStatus;

class TaskCompletedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  TaskStatusChange  $event
     * @return void
     */
    public function handle(TaskStatusChange $event)
    {
        $task = $event->task;

        if ($task->status != TaskStatus::STATUS_COMPLETED)
            return;

        if ( ! $task->device)
            return;

        $position = $task->device->positionTraccar();

        $event = Event::create([
            'type'         => Event::TYPE_TASK_COMPLETE,
            'user_id'      => $task->user_id,
            'device_id'    => $task->device_id,
            'alert_id'     => null,
            'geofence_id'  => null,
            'altitude'     => $position ? $position->altitude : null,
            'course'       => $position ? $position->course : null,
            'latitude'     => $position ? $position->latitude : null,
            'longitude'    => $position ? $position->longitude : null,
            'speed'        => $position ? $position->speed : null,
            'time'         => Carbon::now(),
            'additional'   => [
                'task' =>  $task->title,
            ],

        ]);

        SendQueue::create([
            'user_id'   => $event->user_id,
            'type'      => $event->type,
            'data'      => $event,
            'channels'  => [
                'push' => true
            ],
        ]);
    }
}
