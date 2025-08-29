<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class BitCut implements Extraction
{
    /**
     * @var int
     */
    protected $start;

    /**
     * @var int
     */
    protected $mask;

    protected $base;

    public function __construct($start, $count, $base)
    {
        $this->start = intval($start);
        $this->mask = (1 << (intval($count))) - 1;
        $this->base = $base == 16;
    }

    public function parse($value)
    {
        try {
            if ($this->base) {
                $value = intval(base_convert($value, 16, 10));
            }

            $value = ($value >> $this->start) & $this->mask;
        } catch (\Exception $e) {
            $value = null;
        }

        return $value === false ? null : $value;
    }
}