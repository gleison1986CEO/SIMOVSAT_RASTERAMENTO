<?php namespace App\Http\Controllers\Frontend;

use App\Exceptions\ResourseNotFoundException;
use App\Http\Controllers\Controller;
use CustomFacades\ModalHelpers\RouteModalHelper;
use Illuminate\Support\Facades\Response;
use Tobuli\Importers\Geofence\GeofenceImportManager;
use Tobuli\Importers\Route\RouteImportManager;

class RoutesController extends Controller
{
    public function index()
    {
        $data = RouteModalHelper::get();

        return !$this->api ? view('front::Routes.index')->with($data) : $data;
    }

    public function store()
    {
        return RouteModalHelper::create();
    }

    public function update()
    {
        return RouteModalHelper::edit();
    }

    public function changeActive()
    {
        return RouteModalHelper::changeActive();
    }

    public function destroy()
    {
        return RouteModalHelper::destroy();
    }

    public function importModal()
    {
        return view('front::Routes.import');
    }

    public function import(RouteImportManager $importManager)
    {
        $this->checkException('routes', 'store');

        $file = request()->file('file');

        if (is_null($file))
            throw new ResourseNotFoundException(trans('validation.attributes.file'));

        if ( ! $file->isValid())
            throw new \Exception(trans('front.unsupported_format'));

        $importManager->import($file);

        return Response::json(['status' => 1]);
    }
}
