<?php

namespace Tobuli\History\Actions;


class OverspeedRoads extends Overspeed
{
    static public function required()
    {
        return array_merge(parent::required(), [
            AppendOverspeedRoads::class
        ]);
    }
}