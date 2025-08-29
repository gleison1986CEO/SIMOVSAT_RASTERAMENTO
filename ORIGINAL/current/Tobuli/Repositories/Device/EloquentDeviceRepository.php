<?php namespace Tobuli\Repositories\Device;

use Dompdf\Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Tobuli\Entities\Device as Entity;
use Tobuli\Repositories\EloquentRepository;

class EloquentDeviceRepository extends EloquentRepository implements DeviceRepositoryInterface {

    public function __construct( Entity $entity )
    {
        $this->entity = $entity;
        $this->searchable = [
            'devices.name',
            'devices.imei',
            'devices.sim_number'
        ];
    }

    public function find($id) {
        return $this->entity->with('users', 'sensors')->find($id);
    }

    public function whereUserId($user_id) {
        return $this->entity->where(['user_id' => $user_id])->with('traccar', 'icon')->get();
    }

    public function userCount($user_id) {
        return $this->entity->where(['user_id' => $user_id])->count();
    }

    public function updateWhereIconIds($ids, $data)
    {
        $this->entity->whereIn('icon_id', $ids)->update($data);
    }

    public function whereImei($imei) {
        return $this->entity->where('imei', $imei)->first();
    }

    public function searchAndPaginateAdmin(array $data, $sort_by, $sort = 'asc', $limit = 1, $where_in)
    {
        $data = $this->generateSearchData($data);
        $sort = array_merge([
            'sort' => $sort,
            'sort_by' => $sort_by
        ], $data['sorting']);

        $items = $this->entity
            ->traccarJoin()
            ->select(['devices.*', 'traccar.server_time', 'traccar.time'])
            ->orderBy($sort['sort_by'], $sort['sort'])
            ->with(['users', 'traccar', 'sensors'])
            ->search(array_get($data,'search_phrase'))
            ->groupBy('devices.id');
            if (!empty($where_in)) {
                $items->join("user_device_pivot", 'devices.id', '=', 'user_device_pivot.device_id')
                    ->whereIn('user_device_pivot.user_id', $where_in);
            }
            $items = $items->paginate($limit);

        $items->sorting = $sort;

        return $items;
    }

    public function searchAndPaginate(array $data, $sort_by, $sort = 'asc', $limit = 10)
    {
        $data = $this->generateSearchData($data);
        $sort = array_merge([
            'sort' => $sort,
            'sort_by' => $sort_by
        ], $data['sorting']);

        $items = $this->entity
            ->traccarJoin()
            ->select(['devices.*', 'traccar.server_time', 'traccar.time'])
            ->orderBy($sort['sort_by'], $sort['sort'])
            ->with('users')
            ->where(function ($query) use ($data) {
                if (!empty($data['search_phrase'])) {
                    foreach ($this->searchable as $column) {
                        $query->orWhere($column, 'like', '%' . $data['search_phrase'] . '%');
                    }
                }

                if (count($data['filter'])) {
                    foreach ($data['filter'] as $key=>$value) {
                        $query->where($key, $value);
                    }
                }
            })
            ->paginate($limit);

        $items->sorting = $sort;

        return $items;
    }

    public function getProtocols($ids) {
        return $this->entity
            ->traccarJoin()
            ->distinct('traccar.protocol')
            ->whereIn('devices.id', $ids)
            ->whereNotNull('traccar.protocol')
            ->get();
    }

    public function setUnregisterdDevice($data, $times = 1)
    {
        $imei     = array_get($data, 'imei');
        $protocol = array_get($data, 'protocol');
        $ip       = array_get($data, 'attributes.ip');


        DB::connection('traccar_mysql')->statement(
            DB::raw("
          INSERT INTO `unregistered_devices_log` (imei, port, times, ip)
          (SELECT :imei1 AS imei, port, $times as times, :ip1 AS ip FROM gpswox_web.tracker_ports WHERE `name` = :protocol)
          UNION
          (SELECT :imei2 AS imei, 80 AS port, $times as times, :ip2 AS ip)
          LIMIT 1
          ON DUPLICATE KEY UPDATE times = (times + $times), ip = :ip3"),
            [
                'imei1' => $imei,
                'imei2' => $imei,
                'ip1' => $ip,
                'ip2' => $ip,
                'ip3' => $ip,
                'protocol' => $protocol,
            ]
        );

    }

    public function getByImeiProtocol($imei, $protocol)
    {
        if ($protocol == 'tk103' && strlen($imei) > 11) {
            $device = $this->findWhere(function ($query) use ($imei) {
                $query->where('imei', 'like', '%' . substr($imei, -11));
            });
        } else {
            $device = $this->findWhere(['imei' => $imei]);
        }

        return $device;
    }
}
