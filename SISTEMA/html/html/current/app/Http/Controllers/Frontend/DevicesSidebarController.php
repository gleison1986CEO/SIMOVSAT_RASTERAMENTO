<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\AbstractSidebarItemsController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Sensors\Types\Blocked;

/**
 * @property Device $itemModel
 */
class DevicesSidebarController extends AbstractSidebarItemsController
{
    protected string $repo = 'devices';
    protected string $viewDir = 'front::Objects';
    protected string $nextRoute = 'objects.sidebar';
    protected string $groupClass = DeviceGroup::class;

    private bool $filterAll = false;

    public function items()
    {
        $this->filterAll = true;

        return parent::items();
    }

    protected function getGroupItemsQuery($groupId, $search)
    {
        $query = $this->user
            ->devices()
            ->with(['traccar', 'sensors' => function (HasMany $query) {
                $types = ['speed'];

                if (Blocked::isEnabled()) {
                    $types[] = 'blocked';
                }

                $query->whereIn('type', $types);
            }]);

        if ($search) {
            $query->search($search);
        }

        //optimization form db index
        $query->whereIn('devices.id', function($q) use ($groupId) {
            $q->select('device_id')->from('user_device_pivot')->where('user_id', $this->user->id);

            if (!$this->filterAll)
                $q->where('group_id', $groupId);

        });

        return $this->filterAll
            ? $query->filter(request()->all())
            : $query->filterGroupId($groupId);
    }
}
