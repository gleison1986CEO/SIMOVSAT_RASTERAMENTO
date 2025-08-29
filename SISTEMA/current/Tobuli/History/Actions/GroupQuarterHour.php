<?php

namespace Tobuli\History\Actions;

use Formatter;
use Tobuli\History\Group;

class GroupQuarterHour extends ActionGroup
{
    const KEY = 'quarter';

    private $current;

    /**
     * @var Group
     */
    private $group;

    static public function required()
    {
        return [
            AppendDuration::class
        ];
    }

    public function boot()
    {
    }

    public function proccess($position)
    {
        $quarter = $this->getQuarter($position);

        if ($this->current == $quarter)
            return;

        if ( ! is_null($this->group))
            $this->history->groupEnd($this->group->getKey(), $position);

        $position->quarter = $quarter;

        $this->group = new Group(self::KEY);

        $this->history->groupStart($this->group, $position);

        $this->current = $quarter;
    }

    protected function getQuarter($position)
    {
        $converted = Formatter::time()->timestamp($position->time);
        $rounded = floor($converted / (15 * 60)) * (15 * 60);

        return date('Y-m-d H:i:s', $rounded);
    }
}