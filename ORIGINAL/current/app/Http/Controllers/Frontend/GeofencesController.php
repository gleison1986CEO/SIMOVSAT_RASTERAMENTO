<?php namespace App\Http\Controllers\Frontend;

use App\Exceptions\ResourseNotFoundException;
use App\Http\Controllers\Controller;
use App\Transformers\Geofence\GeofenceMapTransformer;
use CustomFacades\ModalHelpers\GeofenceModalHelper;
use CustomFacades\Repositories\GeofenceGroupRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Geofence;
use Tobuli\Entities\GeofenceGroup;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\Geofence\GeofenceImportManager;

use FractalTransformer;

class GeofencesController extends Controller
{
    const LOAD_LIMIT = 100;

    public function index()
    {
        if ($this->api) {
            try {
                $this->checkException('geofences', 'view');

                return ['items' => [
                    'geofences' => Geofence::where('user_id', $this->user->id)->get()
                ]];
            } catch (\Exception $e) {
                return ['items' => ['geofences' => []]];
            }
        }

        if ( ! request()->wantsJson())
            return request()->filled('page')
                ? view('front::Geofences.items')->with([
                    'geofences' => $this->getGroupItems(request()->get('group_id'), request()->get('s'))
                ])
                : $this->getGroups();

        $items = Geofence::where('user_id', $this->user->id)->paginate(500);

        return response()->json(
            FractalTransformer::paginate($items, GeofenceMapTransformer::class)->toArray()
        );
    }

    protected function getGroups()
    {
        $groups_opened = json_decode($this->user->open_geofence_groups, TRUE);

        $groups = GeofenceGroup::where(['user_id' => $this->user->id])
            ->orderBy('title')
            ->get()
            ->prepend(new GeofenceGroup([
                'id'    => 0,
                'title' => trans('front.ungrouped')
            ]));

        $groups = $groups->transform(function($group) use ($groups_opened) {
            $group_id = $group->id ?? 0;

            return [
                'id'        => $group_id ?? 0,
                'title'     => $group->title,
                'open'      => ($groups_opened && in_array($group_id, $groups_opened)),
                'geofences' => $this->getGroupItems($group->id ?? 0, request()->get('s'))
            ];
        })->filter(function($group) {
            return $group['geofences']->count();
        });

        return view('front::Geofences.groups')->with(compact('groups'));
    }

    protected function getGroupItems($group_id, $search)
    {
        $query = Geofence::where('user_id', $this->user->id);

        if ($search)
            $query->search($search);

        if ( ! is_null($group_id)) {
            if ($group_id)
                $query->where('group_id', $group_id);
            else
                $query->whereNull('group_id');
        }

        return $query
            ->paginate(self::LOAD_LIMIT)
            ->appends([
                'group_id' => $group_id,
                's' => $search
            ]);
    }

    public function create()
    {
        if (!$this->user->perm('geofences', 'edit'))
            return ['status' => 0, 'perm' => 0];

        return ['status' => 1];
    }

    public function store()
    {
        return GeofenceModalHelper::create();
    }

    public function update()
    {
        return GeofenceModalHelper::edit();
    }

    public function changeActive()
    {
        return GeofenceModalHelper::changeActive();
    }

    public function destroy()
    {
        return GeofenceModalHelper::destroy();
    }

    public function importModal()
    {
        return view('front::Geofences.import');
    }

    public function import(GeofenceImportManager $importManager)
    {
        $this->checkException('geofences', 'store');

        $file = request()->file('file');

        if (is_null($file))
            throw new ResourseNotFoundException(trans('validation.attributes.file'));

        if ( ! $file->isValid())
            throw new ValidationException(['file' => trans('front.unsupported_format')]);

        $importManager->import($file);

        return Response::json([
            'status' => 1,
            'message' => trans('front.successfully_updated_geofence')
        ]);
    }

    public function export()
    {
        $data = GeofenceModalHelper::exportData();

        return !$this->api ? view('front::Geofences.export')->with($data) : $data;
    }

    public function exportCreate()
    {
        $data = GeofenceModalHelper::export();

        header('Content-disposition: attachment; filename=geofences_export.gexp');
        header('Content-type: text/plain');

        echo json_encode($data);
    }

    public function exportType()
    {
        return GeofenceModalHelper::exportType();
    }

    public function pointIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);

        if ($validator->fails())
            return response()->json(['status' => 0, 'errors' => $validator->errors()]);

        if (is_null($geofences = $this->user->geofences))
            throw new ResourseNotFoundException(trans('front.geofences'));

        return response()->json([
            'status' => 1,
            'zones'  => getPointZones($geofences, $request->lat, $request->lng)
        ]);
    }
}
