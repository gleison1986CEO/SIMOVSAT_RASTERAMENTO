<?php namespace Tobuli\Reports\Reports;


class OverspeedCustomSummaryReport extends OverspeedCustomReport
{
    const TYPE_ID = 34;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.overspeed_custom_summary');
    }
}