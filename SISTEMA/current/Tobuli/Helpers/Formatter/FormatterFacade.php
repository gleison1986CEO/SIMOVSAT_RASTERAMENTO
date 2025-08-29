<?php

namespace Tobuli\Helpers\Formatter;

use Illuminate\Support\Facades\Facade;

class FormatterFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Formatter';
    }
}