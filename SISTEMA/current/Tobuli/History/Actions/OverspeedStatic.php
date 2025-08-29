<?php

namespace Tobuli\History\Actions;


class OverspeedStatic extends Overspeed
{
    static public function required()
    {
        return array_merge(parent::required(), [
            AppendOverspeedStatic::class
        ]);
    }
}