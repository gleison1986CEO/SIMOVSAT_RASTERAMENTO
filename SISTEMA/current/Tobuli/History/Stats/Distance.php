<?php

namespace Tobuli\History\Stats;

use Formatter;
use Tobuli\Helpers\Formatter\Formattable;

class Distance extends StatSum
{
    use Formattable;

    protected $skipped;

    public function __construct()
    {
        parent::__construct();

        $this->setFormatUnit(Formatter::distance());
    }

    public function apply($value)
    {
        if ( ! $this->skipped && $this->skipped = true)
            return;

        parent::apply($value);
    }


    public function __clone()
    {
        parent::__clone();

        $this->skipped = null;
    }
}