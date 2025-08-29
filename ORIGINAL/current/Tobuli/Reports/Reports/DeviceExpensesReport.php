<?php namespace Tobuli\Reports\Reports;


use Tobuli\Reports\DeviceReport;

class DeviceExpensesReport extends DeviceReport
{
    const TYPE_ID = 46;

    public static function isEnabled()
    {
        return expensesTypesExist();
    }

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.expenses');
    }

    protected function generateDevice($device)
    {
        $query = $device->expenses()
            ->whereBetween('date', [$this->date_from, $this->date_to])
            ->with('type');

        if (($type = $this->parameters['expense_type']) != 'all') {
            $query->whereHas('type', function ($q) use ($type) {
                $q->where('id', $type);
            });
        }

        if (($supplier = $this->parameters['supplier']) != 'all') {
            $query->where('supplier', $supplier);
        }

        $expenses = $query->get();

        return [
            'meta' => $this->getDeviceMeta($device),
            'data' => [
                'expenses' => $expenses->toArray(),
                'sum'      => $expenses->sum(function ($expense) { return $expense->total; }),
            ],
        ];
    }

}