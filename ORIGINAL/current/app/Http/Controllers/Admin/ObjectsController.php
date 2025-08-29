<?php namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourseNotFoundException;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Repositories\DeviceRepo;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Exporters\Device\ExporterManager;
use Tobuli\Helpers\Tracker;
use Tobuli\Validation\ClientFormValidator;
use Tobuli\Repositories\Device\DeviceRepositoryInterface as Device;
use Tobuli\Repositories\Event\EventRepositoryInterface as Event;
use Validator;

class ObjectsController extends BaseController
{
    /**
     * @var ClientFormValidator
     */
    private $clientFormValidator;

    private $section = 'objects';

    /**
     * @var Device
     */
    private $device;

    /**
     * @var Event
     */
    private $event;

    function __construct(
        ClientFormValidator $clientFormValidator,
        Device $device,
        Event $event
    ) {
        parent::__construct();
        $this->clientFormValidator = $clientFormValidator;
        $this->device = $device;
        $this->event = $event;
    }

    public function index()
    {
        $input = Input::all();
        $users = null;
        if ($this->user->isManager()) {
            $users = $this->user->subusers()->pluck('id', 'id')->all();
            $users[] = $this->user->id;
        }

        $items = $this->device->searchAndPaginateAdmin($input, 'name', 'asc', 20, $users);
        $section = $this->section;

        return View::make('admin::' . ucfirst($this->section) . '.' . (Request::ajax() ? 'table' : 'index'))
            ->with(compact('items', 'section'));
    }

    public function create()
    {
        $managers = UserRepo::getOtherManagers(0)
            ->pluck('email', 'id')
            ->prepend('-- ' . trans('admin.select') . ' --', '0')
            ->all();

        return View::make('admin::' . ucfirst($this->section) . '.create')->with(compact('managers'));
    }

    public function destroy()
    {
        if (config('tobuli.object_delete_pass') && isAdmin() && request('password') != config('tobuli.object_delete_pass')) {
            return ['status' => 0, 'errors' => ['message' => trans('front.login_failed')]];
        }

        $ids = Input::get('ids');

        if (is_array($ids) && count($ids)) {
            foreach ($ids as $id) {
                $item = DeviceRepo::find($id);

                if (empty($item) || ( ! $this->user->can('remove', $item)))
                    continue;

                beginTransaction();

                try {
                    $item->remove();
                    commitTransaction();
                } catch (\Exception $e) {
                    rollbackTransaction();
                }
            }
        }

        return Response::json(['status' => 1]);
    }

    public function doDestroy()
    {
        return view('admin::Objects.destroy', ['ids' => request('id')]);
    }

    public function setActiveMulti($active)
    {
        $this->data['active'] = (bool)$active;

        $validator = Validator::make($this->data, [
            'id'     => 'required|array',
            'id.*'   => 'integer',
            'active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->messages());
        }

        \Auth::user()
            ->accessibleDevices()
            ->whereIn('id', $this->data['id'])
            ->update(['active' => $this->data['active']]);

        return Response::json(['status' => 1]);
    }

    public function restartTraccar()
    {
        $tracker = new Tracker();
        $tracker->actor($this->user)->restart();

        return redirect()->back();
    }

    public function import()
    {
        return View::make('admin::' . ucfirst($this->section) . '.import');
    }

    public function importSet()
    {
        $file = Request::file('file');

        if ( ! $file->isValid())
            return;

        $manager = new \Tobuli\Importers\Device\DeviceImportManager();
        $manager->import($file->getPathName());

        return Response::json(['status' => 1]);
    }

    public function export()
    {
        $devices = $this->user->accessibleDevices();

        $fields = request()->get('fields', []);

        $firstDevice = $devices->first();

        if (!$firstDevice)
            return Response::json(['status' => 0]);

        foreach ($fields as $index => $field) {
            if ($this->user->can('view', $firstDevice, $field))
                continue;

            unset($fields[$index]);
        }

        $exporter = new ExporterManager($devices, $fields);

        return $exporter->download(request('format'));
    }

    public function exportModal()
    {
        return view('admin::' . ucfirst($this->section) . '.export', [
            'formats' => config('tobuli.exports.formats'),
            'fields'  => \Tobuli\Entities\Device::getFields()
        ]);
    }

    public function expiration(\Illuminate\Http\Request $request, $imei)
    {
        $validator = Validator::make($request->all(), [
            'expiration_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            throw new ValidationException( $validator->messages() );
        }

        $device = $this->device->findWhere(['imei' => $imei]);

        if ( ! $device)
           throw new ResourseNotFoundException('global.device');

        $device->update(['expiration_date' => $request->input('expiration_date')]);

        return Response::json(['status' => 1]);
    }

    public function bulkDelete()
    {
        if ( ! $this->user->isAdmin())
            throw new AuthorizationException();

        $validator = Validator::make(request()->all(), ['file' => 'required']);

        if ($validator->fails())
            throw new ValidationException($validator->messages());

        $file = request()->file('file');

        if (is_null($file) || $file->getClientOriginalExtension() != 'csv')
            throw new ValidationException('Only CSV');

        $source = file_get_contents($file);
        $rows = str_getcsv($source, "\n");

        if (empty($rows)) {
            return null;
        }
        $headers = array_shift($rows);
        $imeis = $rows;

        $errors_count = 0;
        $content = trans('admin.logs') . " <br>";

        if (is_array($imeis) && count($imeis)) {
            foreach ($imeis as $imei) {
                $device = DeviceRepo::whereImei($imei);

                if (empty($device)) {
                    $content .= trans('validation.attributes.imei') . "($imei) ". trans('global.not_found') ."<br>";
                    $errors_count++;
                    continue;
                }

                if ($this->removeDevice($device) == false) {
                    $content .= trans('global.device') . "($imei) " . trans('global.failed') . "<br>";
                    $errors_count++;
                }
            }
        }

        $content .= "<br>" . trans('global.successful') . " " . lcfirst(trans('global.count')) . ": " . (count($imeis) - $errors_count);
        $content .= "<br>" . trans('global.failed') . " " . lcfirst(trans('global.count')) . ":  $errors_count";

        return Response::json([
            'status'  => 0,
            'content' => $content,
            'trigger' => 'bulk_delete_object',
        ]);
    }

    public function bulkDeleteModal()
    {
        return view('admin::' . ucfirst($this->section) . '.bulk_delete');
    }

    private function removeDevice($device)
    {
        beginTransaction();

        try {
            $device->remove();
            commitTransaction();
        } catch (\Exception $e) {
            rollbackTransaction();
            return false;
        }

        return true;
    }
}
