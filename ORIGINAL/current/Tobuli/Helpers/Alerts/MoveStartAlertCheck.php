<?php

namespace Tobuli\Helpers\Alerts;

use Carbon\Carbon;
use Tobuli\Entities\Event;

class MoveStartAlertCheck extends AlertCheck
{
    public function checkEvents($position, $prevPosition)
    {
        if ( ! $this->check($position))
            return null;

        $event = $this->getEvent();

        $event->type = Event::TYPE_MOVE_START;
        $event->message = '';

        $this->silent($event);

        return [$event];
    }

    public function check($position)
    {
        if ( ! $position)
            return false;

        if ($position->speed < $this->device->min_moving_speed)
            return false;

        $moved_at = $this->device->traccar->moved_at ?? null;

        if ($moved_at && (strtotime($position->time) - strtotime($moved_at) < $this->getTimeDuration())) {
            return false;
        }

        if ( ! $this->checkAlertPosition($position))
            return false;

        return true;
    }

    protected function getTimeDuration()
    {
        return 180;
    }
}