<?php namespace App\Http\Controllers\Admin;



use App\Notifications\PopupNotification;
use CustomFacades\Repositories\PopupRepo;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Tobuli\Entities\Popup;
use Tobuli\Entities\User;
use Tobuli\Services\NotificationService;

class PopupsController extends BaseController {

    private $section = 'popups';

    private $notificationService;

    public function __construct(NotificationService $notificationService) {
        $this->notificationService = $notificationService;
    }

    public function index() {
        $items = PopupRepo::searchAndPaginate( Input::all(), 'id');

        return View::make('admin::'.ucfirst($this->section).'.' . (Request::ajax() ? 'table' : 'index'))->with(['section'=>$this->section, 'items'  => $items]);
    }

    public function create() {
        return View::make('admin::'.ucfirst($this->section).'.' . 'create')->with(['item' => new Popup(),'positions' => Popup::getPositions()]);
    }

    public function edit($id) {
        $item = PopupRepo::find($id);

        if (empty($item))
            return modalError(dontExist('global.event'));

        return View::make('admin::'.ucfirst($this->section).'.edit')->with(['item' => $item, 'positions' => Popup::getPositions()]);
    }

    public function store() {
        $input = Input::all();
        if ($this->notificationService->save( $input )) {
            return Response::json(['success'=>true, 'status' => 1]);
        }

        return Response::json(['success'=>false, 'status' => 1]);

    }

    public function update() {
        $input = Input::all();

        if ($this->notificationService->save( $input )) {
            return Response::json(['success'=>true, 'status' => 1]);
        }

        return Response::json(['success'=>false, 'status' => 1]);
    }

}
