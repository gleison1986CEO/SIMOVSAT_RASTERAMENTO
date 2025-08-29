<?php namespace App\Http\Controllers\Frontend;

use App\Exceptions\ResourseNotFoundException;
use App\Http\Controllers\Controller;
use App\Transformers\Poi\PoiMapTransformer;
use App\Transformers\Poi\PoiTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Tobuli\Entities\MapIcon;
use Tobuli\Entities\PoiGroup;
use Tobuli\Entities\Poi;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\POI\POIImportManager;
use Tobuli\Services\PoiUserService;
use FractalTransformer;

class PoisController extends Controller
{
    const LOAD_LIMIT = 100;

    /**
     * @var PoiUserService
     */
    protected $service;

    protected function afterAuth($user)
    {
        $this->service = new PoiUserService($this->user);
    }

    public function index()
    {
        $this->checkException('poi', 'view');

        if ( ! request()->wantsJson())
            return request()->filled('page')
                ? view('front::Pois.items')->with([
                    'pois' => $this->getGroupItems(request()->get('group_id'), request()->get('s'))
                ])
                : $this->getGroups();

        $items = Poi::where('user_id', $this->user->id)->with(['mapIcon'])->paginate(500);

        return response()->json(
            FractalTransformer::paginate($items, PoiMapTransformer::class)->toArray()
        );
    }

    protected function getGroups()
    {
        $groups = PoiGroup::where(['user_id' => $this->user->id])
            ->orderBy('title')
            ->get()
            ->prepend(new PoiGroup([
                'id' => 0,
                'title' => trans('front.ungrouped'),
                'open' => true,
            ]));

        $groups = $groups->transform(function($group) {
            return [
                'id'    => $group->id ?? 0,
                'title' => $group->title,
                'open'  => $group->open,
                'pois' => $this->getGroupItems($group->id ?? 0, request()->get('s'))
            ];
        })->filter(function($group) {
            return $group['pois']->count();
        });

        return view('front::Pois.groups')->with(compact('groups'));
    }

    protected function getGroupItems($group_id, $search)
    {
        $query = Poi::where('user_id', $this->user->id)->with(['mapIcon']);

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

    public function store(Request $request)
    {
        $this->service->create($request->all());

        return ['status' => 1];
    }

    public function update(Request $request)
    {
        $item = Poi::find($request->get('id'));

        $this->service->edit($item, $request->all());

        return ['status' => 1];
    }

    public function changeActive(Request $request)
    {
        $id = $request->get('id');
        $status = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        if (is_array($id)) {
            $items = Poi::findMany($id);

            $this->service->activeMulti($items, $status);
        } else {
            $item = Poi::find($id);

            $this->service->active($item, $status);
        }

        return ['status' => 1];
    }

    public function destroy(Request $request)
    {
        $item = Poi::find($request->get('id'));

        $this->service->remove($item);

        return ['status' => 1];
    }

    public function import_form()
    {
        $icons = MapIcon::all();

        return view('front::Pois.import')->with(compact('icons'));
    }

    public function import(POIImportManager $importManager)
    {
        $this->checkException('poi', 'store');

        $file = request()->file('file');

        if (is_null($file))
            throw new ResourseNotFoundException(trans('validation.attributes.file'));

        if ( ! $file->isValid())
            throw new ValidationException(['id' => trans('front.unsupported_format')]);

        $additionals = request()->all(['map_icon_id']);
        $importManager->import($file, $additionals);

        return Response::json(['status' => 1]);
    }
}
