<?php

namespace Tobuli\History\Stats;

use Formatter;
use Tobuli\Helpers\Formatter\Formattable;

class Duration extends StatSum
{
    use Formattable;

    protected $skipped = false;

    public function __construct()
    {
        parent::__construct();

        $this->setFormatUnit(Formatter::duration());
    }

    public function apply($value)
    {
        if ( ! $this->skipped && $this->skipped = true) {
            return;
        }

        parent::apply($value);
    }

    public function __clone()
    {
        $this->skipped = null;
        parent::__clone();
    }

}