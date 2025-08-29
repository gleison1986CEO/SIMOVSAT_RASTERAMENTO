<?php namespace ModalHelpers;

use CustomFacades\Repositories\GeofenceGroupRepo;
use CustomFacades\Repositories\GeofenceRepo;
use CustomFacades\Validators\GeofenceFormValidator;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Device;
use Tobuli\Entities\GeofenceGroup;
use Tobuli\Exceptions\ValidationException;
use Illuminate\Support\Facades\DB;

class GeofenceModalHelper extends ModalHelper
{
    public function get()
    {
        try {
            $this->checkException('geofences', 'view');
        } catch (\Exception $e) {
            return ['geofences' => []];
        }

        $geofences = GeofenceRepo::whereUserId($this->user->id);

        if ($this->api) {
            return compact('geofences');
        }

        $groups_opened = json_decode($this->user->open_geofence_groups, TRUE);

        $groups = GeofenceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->prepend(new GeofenceGroup([
                'id'    => 0,
                'title' => trans('front.ungrouped')
            ]))
            ->mapWithKeys(function($group) use ($groups_opened) {
                $group_id = $group->id ?? 0;

                return [$group_id => [
                    'id'      => $group_id,
                    'title'   => $group->title,
                    'open'    => ($groups_opened && in_array($group_id, $groups_opened)),
                ]];
            })
            ->all();

        $grouped = $geofences->groupBy('group_id');

        return compact('grouped', 'groups');
    }

    public function create()
    {
        $this->checkException('geofences', 'store');

        $this->validate('create');

        $geofence = GeofenceRepo::create($this->data + ['user_id' => $this->user->id]);

        return ['status' => 1, 'item' => $geofence];
    }

    public function edit()
    {
        $item = GeofenceRepo::find($this->data['id']);

        $this->checkException('geofences', 'update', $item);

        $this->validate('update');

        GeofenceRepo::updateWithPolygon($item->id, $this->data);

        return ['status' => 1];
    }

    private function validate($type)
    {
        $polygon = empty($this->data['polygon']) ? [] : $this->data['polygon'];

        if (!is_array($polygon))
            $polygon = json_decode($polygon, TRUE);

        if (empty($polygon))
            unset($this->data['polygon']);

        if (empty($polygon))
            unset($this->data['polygon']);

        if (array_key_exists('device_id', $this->data)) {
            if (empty($this->data['device_id']))
                $this->data['device_id'] = null;

            if ($this->data['device_id'] && $device = Device::find($this->data['device_id']))
                if (!$this->user->can('view', $device))
                    unset($this->data['device_id']);
        }

        GeofenceFormValidator::validate($type, $this->data);
    }

    public function changeActive()
    {
        $validator = Validator::make($this->data, [
            'id' => 'required_without:group_id',
            'group_id' => 'required_without:id',
        ]);

        if ($validator->fails())
            throw new ValidationException($validator->errors());

        $active = (isset($this->data['active']) && filter_var($this->data['active'], FILTER_VALIDATE_BOOLEAN)) ? 1 : 0;

        $query = DB::table('geofences')->where('user_id', $this->user->id);

        if (array_key_exists('group_id', $this->data)) {
            if ($group_id = $this->data['group_id']) {
                $group_id = is_array($group_id) ? $group_id : [$group_id];
                $query->whereIn('group_id',$group_id);
            } else {
                $query->whereNull('group_id');
            }
        } else {
            if ($id = $this->data['id']) {
                $id = is_array($id) ? $id : [$id];
                $query->whereIn('id',$id);
            }
        }
        
        $query->update([
            'active' => $active
        ]);

        return ['status' => 1];
    }

    public function import($content = NULL)
    {
        $this->checkException('geofences', 'store');

        if (is_null($content))
            $content = $this->data['content'];

        libxml_use_internal_errors(true);

        $arr = @json_decode($content, TRUE);
        $xml = simplexml_load_string($content);
        if (!is_array($arr) && !$xml)
            return ['status' => 0, 'error' => trans('front.unsupported_format')];

        $groups_nr = 0;
        $geofences_nr = 0;
        $geofences_exists_nr = 0;

        try {
            // Default goefences format
            if (is_array($arr)) {
                $groups = [];
                if (!empty($arr['groups'])) {
                    foreach ($arr['groups'] as $group) {
                        if ($group['id'] == 0)
                            continue;

                        $item = GeofenceGroupRepo::create([
                            'user_id' => $this->user->id,
                            'title' => $group['title']
                        ]);

                        $groups[$group['id']] = $item->id;
                        $groups_nr++;
                    }
                }
                if (!empty($arr['geofences'])) {
                    foreach ($arr['geofences'] as $geofence) {
                        $group_id = null;
                        if (isset($groups[$geofence['group_id']]))
                            $group_id = $groups[$geofence['group_id']];

                        $polygon = json_decode($geofence['coordinates'], TRUE);

                        $item = GeofenceRepo::findWhere(['coordinates' => json_encode($polygon), 'user_id' => $this->user->id]);
                        if (empty($item)) {
                            $geofences_nr++;
                            GeofenceRepo::create([
                                'user_id' => $this->user->id,
                                'group_id' => $group_id,
                                'name' => $geofence['name'],
                                'polygon' => $polygon,
                                'polygon_color' => $geofence['polygon_color']
                            ]);
                        }
                        else
                            $geofences_exists_nr++;
                    }
                }
            }

            // KML
            if ($xml) {
                $color = '#d000df';

                foreach ( $xml->Document->xpath('//Style') as $style) {
                    $color = '#'.$style->PolyStyle->color;
                }


                $folders = $xml->xpath("//*[name()='Folder']");

                if ( $folders ) {
                    foreach ( $folders as $folder ) {

                        $geoGroup = GeofenceGroupRepo::findWhere(['title' => $folder->name, 'user_id' => $this->user->id]);

                        if (empty($geoGroup)) {
                            $groups_nr++;
                            $geoGroup = GeofenceGroupRepo::create([
                                'user_id' => $this->user->id,
                                'title' => $folder->name,
                            ]);
                        }

                        foreach ( $folder->Placemark as $mark) {

                            $mark = json_decode(json_encode($mark), true);

                            $group_id = $geoGroup->id;
                            $polygon = [];
                            $coordinates = explode(" ", $mark['Polygon']['outerBoundaryIs']['LinearRing']['coordinates']);
                            if (empty($coordinates))
                                continue;
                            foreach ($coordinates as $cord) {
                                if (empty($cord))
                                    continue;

                                list($lng, $lat) = explode(',', $cord);
                                array_push($polygon, ['lat' => $lat, 'lng' => $lng]);
                            }
                            array_pop($polygon);

                            $item = GeofenceRepo::findWhere(['coordinates' => json_encode($polygon), 'user_id' => $this->user->id]);
                            if (empty($item)) {
                                $geofences_nr++;
                                GeofenceRepo::create([
                                    'active' => 0,
                                    'user_id' => $this->user->id,
                                    'group_id' => $group_id,
                                    'name' => $mark['name'],
                                    'polygon' => $polygon,
                                    'polygon_color' => $color
                                ]);
                            }
                            else
                                $geofences_exists_nr++;
                        }
                    }
                } else {

                    foreach ( $xml->xpath("//*[name()='Placemark']") as $mark) {
                        $mark = json_decode(json_encode($mark), true);

                        $group_id = null;
                        $polygon = [];
                        $coordinates = explode(" ", $mark['Polygon']['outerBoundaryIs']['LinearRing']['coordinates']);
                        if (empty($coordinates))
                            continue;
                        foreach ($coordinates as $cord) {
                            if (empty($cord))
                                continue;

                            list($lng, $lat) = explode(',', $cord);
                            array_push($polygon, ['lat' => $lat, 'lng' => $lng]);
                        }
                        array_pop($polygon);

                        $item = GeofenceRepo::findWhere(['coordinates' => json_encode($polygon), 'user_id' => $this->user->id]);
                        if (empty($item)) {
                            $geofences_nr++;
                            GeofenceRepo::create([
                                'active' => 0,
                                'user_id' => $this->user->id,
                                'group_id' => $group_id,
                                'name' => $mark['name'],
                                'polygon' => $polygon,
                                'polygon_color' => $color
                            ]);
                        }
                        else
                            $geofences_exists_nr++;
                    }
                }
            }
        }
        catch (\Exception $e) {
            return ['status' => 0, 'error' => trans('front.unsupported_format')];
        }

        return array_merge(['status' => 1, 'message' => strtr(trans('front.imported_geofences'), [':groups' => $groups_nr, ':geofences' => $geofences_nr])]);
    }

    public function export()
    {
        $this->checkException('geofences', 'view');

        $geofences = [];
        $groups = [];
        if (isset($this->data['groups']) && is_array($this->data['groups'])) {
            $fl_groups = array_flip($this->data['groups']);
            $groups = GeofenceGroupRepo::getWhereInWhere($this->data['groups'], 'id', ['user_id' => $this->user->id])->toArray();
            if (isset($fl_groups['0']))
                $groups[]['id'] = NULL;
            foreach ($groups as &$group) {
                if (isset($group['user_id']))
                    unset($group['user_id']);

                $items = GeofenceRepo::getWhere(['group_id' => $group['id'], 'user_id' => $this->user->id])->toArray();
                foreach ($items as $geofence) {
                    unset($geofence['polygon'], $geofence['user_id'], $geofence['active'], $geofence['created_at'], $geofence['updated_at']);
                    array_push($geofences, $geofence);
                }
            }
        }

        if (isset($this->data['geofences']) && is_array($this->data['geofences'])) {
            $items = GeofenceRepo::getWhereInWhere($this->data['geofences'], 'id', ['user_id' => $this->user->id])->toArray();
            foreach ($items as $geofence) {
                unset($geofence['polygon'], $geofence['user_id'], $geofence['active'], $geofence['created_at'], $geofence['updated_at']);
                $geofence['coordinates'] = json_encode(json_decode($geofence['coordinates'], TRUE));
                array_push($geofences, $geofence);
            }
        }

        return compact('groups', 'geofences');
    }

    public function exportData()
    {
        $export_types = [
            'export_single' => trans('front.export_single'),
            'export_groups' => trans('front.export_groups'),
            'export_active' => trans('front.export_active'),
            'export_inactive' => trans('front.export_inactive')
        ];

        $geofences = GeofenceRepo::getWhere(['user_id' => $this->user->id])->pluck('name', 'id')->all();

        return compact('export_types', 'geofences');
    }

    public function exportType()
    {
        $type = $this->data['type'];
        $selected = null;

        $items = GeofenceRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('name', 'id')
            ->all();

        if ($type == 'export_groups') {
            $items = GeofenceGroupRepo::getWhere(['user_id' => $this->user->id])
                ->pluck('title', 'id')
                ->prepend(trans('front.ungrouped'), '0')
                ->all();
        } elseif ($type == 'export_active') {
            $selected = GeofenceRepo::getWhere(['user_id' => $this->user->id, 'active' => 1])
                ->pluck('id', 'id')
                ->all();
        } elseif ($type == 'export_inactive') {
            $selected = GeofenceRepo::getWhere(['user_id' => $this->user->id, 'active' => 0])
                ->pluck('id', 'id')
                ->all();
        }

        $data = compact('items', 'selected', 'type');
        if ($this->api) {
            return $data;
        }
        else {
            $this->data = $type == 'export_groups' ? 'groups' : 'geofences';
            
            $input = $this->data;
            
            return view('front::Geofences.exportType')->with(array_merge($data, compact('input')));
        }
    }

    public function destroy()
    {
        $id = array_key_exists('geofence_id', $this->data) ? $this->data['geofence_id'] : $this->data['id'];

        $item = GeofenceRepo::find($id);

        $this->checkException('geofences', 'remove', $item);

        GeofenceRepo::delete($id);
        
        return ['status' => 1];
    }
}