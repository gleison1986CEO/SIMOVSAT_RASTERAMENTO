<?php namespace App\Http\Controllers\Admin;
use App\Models\Chip;


use App\Exceptions\DeviceLimitException;
use Tobuli\Entities\EmailTemplate;
use Tobuli\Entities\MapIcon;
use Tobuli\Helpers\Password;
use Tobuli\Importers\Geofence\GeofenceImportManager;
use Tobuli\Importers\POI\POIImportManager;
use Tobuli\Importers\Route\RouteImportManager;
use Tobuli\Services\CustomValuesService;
use Tobuli\Services\UserService;
use Validator;
use CustomFacades\Repositories\UserRepo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Repositories\BillingPlan\BillingPlanRepositoryInterface as BillingPlan;
use Tobuli\Services\PermissionService;
use Tobuli\Validation\ClientFormValidator;
use Tobuli\Repositories\Device\DeviceRepositoryInterface as Device;
use Tobuli\Repositories\TraccarDevice\TraccarDeviceRepositoryInterface as TraccarDevice;
use Tobuli\Repositories\Event\EventRepositoryInterface as Event;
use Tobuli\Repositories\User\UserRepositoryInterface as User;
use CustomFacades\Validators\ObjectsListSettingsFormValidator;

class ClientsController extends BaseController
{
    /**
     * @var ClientFormValidator
     */
    private $clientFormValidator;

    private $section = 'clients';
    /**
     * @var Device
     */
    private $device;
    /**
     * @var TraccarDevice
     */
    private $traccarDevice;
    /**
     * @var Event
     */
    private $event;

    private $permissionService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var CustomValuesService
     */
    private $customValueService;

    function __construct(
        ClientFormValidator $clientFormValidator,
        Device $device,
        TraccarDevice $traccarDevice,
        Event $event,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->clientFormValidator = $clientFormValidator;
        $this->device = $device;
        $this->traccarDevice = $traccarDevice;
        $this->event = $event;
        $this->permissionService = $permissionService;

        $this->userService = new UserService();
        $this->customValueService = new  CustomValuesService();
    }


    

    public function index()
    {
        $input = Input::all();

        $items = UserRepo::searchAndPaginate($input, 'email', 'asc', 20);
        $section = $this->section;

        return $this->api
            ? $items
            : View::make('admin::' . ucfirst($this->section) . '.' . (Request::ajax() ? 'table' : 'index'))->with(compact('items',
                'input', 'section'));
    }

    public function painel_estatisticas()
    {
        $input = Input::all();

        $items = UserRepo::searchAndPaginate($input, 'email', 'asc', 20);
        $section = $this->section;

        return $this->api
            ? $items
            : View::make('admin::' . ucfirst($this->section) . '.' . (Request::ajax() ? 'table' : 'painel_estatisticas'))->with(compact('items',
                'input', 'section'));
    }

    



        //////////////////NOVAS PAGINAS /////////////////////////////////////
        //////////////////NOVAS PAGINAS /////////////////////////////////////
        //////////////////NOVAS PAGINAS /////////////////////////////////////


        public function alertas_veiculos()
        {
            $input = Input::all();
    
            $items = UserRepo::searchAndPaginate($input, 'email', 'asc', 20);
            $section = $this->section;
    
            return $this->api
                ? $items
                : View::make('admin::' . ucfirst($this->section) . '.' . (Request::ajax() ? 'table' : 'alertas_veiculos'))->with(compact('items',
                    'input', 'section'));
        }




        public function alertas_fraude()
        {
            $input = Input::all();
    
            $items = UserRepo::searchAndPaginate($input, 'email', 'asc', 20);
            $section = $this->section;
    
            return $this->api
                ? $items
                : View::make('admin::' . ucfirst($this->section) . '.' . (Request::ajax() ? 'table' : 'alertas_fraude'))->with(compact('items',
                    'input', 'section'));
        }



        public function alertas_conexao()
        {
            $input = Input::all();
    
            $items = UserRepo::searchAndPaginate($input, 'email', 'asc', 20);
            $section = $this->section;
    
            return $this->api
                ? $items
                : View::make('admin::' . ucfirst($this->section) . '.' . (Request::ajax() ? 'table' : 'alertas_conexao'))->with(compact('items',
                    'input', 'section'));
        }



        public function alertas_cerca()
        {
            $input = Input::all();
    
            $items = UserRepo::searchAndPaginate($input, 'email', 'asc', 20);
            $section = $this->section;
    
            return $this->api
                ? $items
                : View::make('admin::' . ucfirst($this->section) . '.' . (Request::ajax() ? 'table' : 'alertas_cerca'))->with(compact('items',
                    'input', 'section'));
        }


        //////////////////NOVAS PAGINAS /////////////////////////////////////
        //////////////////NOVAS PAGINAS /////////////////////////////////////
        //////////////////NOVAS PAGINAS /////////////////////////////////////

    public function create(BillingPlan $billingPlanRepo)
    {
        $managers = UserRepo::getOtherManagers(0)
            ->pluck('email', 'id')
            ->prepend('-- ' . trans('admin.select') . ' --', '0')
            ->all();

        $maps = getAvailableMaps();

        $plans = [];

        if (settings('main_settings.enable_plans')) {
            $plans = $billingPlanRepo->getWhere([], 'objects', 'asc')
                ->pluck('title', 'id')
                ->prepend('-- ' . trans('admin.select') . ' --', '0')
                ->all();
        }

        $objects_limit = null;

        if (hasLimit()) {
            $objects_limit = Auth::User()->devices_limit - getManagerUsedLimit(Auth::User()->id);
            $objects_limit = $objects_limit < 0 ? 0 : $objects_limit;
        }

        $grouped_permissions = $this->permissionService->group(
            $this->permissionService->getByUserRole($this->user->isAdmin() ? null : $this->user)
        );

        $permission_values = $this->permissionService->getUserDefaults();

        $devices = groupDevices($this->user->accessibleDevicesWithGroups()->get(), $this->user);
        $numeric_sensors = config('tobuli.numeric_sensors');
        $settings = UserRepo::getListViewSettings(null);
        $fields = config('tobuli.listview_fields');
        listviewTrans(null, $settings, $fields);

        return View::make('admin::' . ucfirst($this->section) . '.create')->with(compact('managers', 'maps', 'plans',
            'objects_limit', 'grouped_permissions', 'devices', 'fields', 'settings', 'numeric_sensors', 'permission_values'));
    }

    public function store(BillingPlan $billingPlanRepo)
    {
        $input = Input::all();
        unset($input['id']);

        onlyEditables(new \Tobuli\Entities\User(), $this->user, $input);

        if (hasLimit()) {
            $input['enable_devices_limit'] = 1;
        }

        if (isset($input['expiration_date'])) {
            $input['subscription_expiration'] = $input['expiration_date'];
        }

        if ($input['group_id'] != 2) {
            $input['manager_id'] = null;
        }

        if (!empty($input['password_generate'])) {
            $input['password'] = $input['password_confirmation'] = Password::generate();
        }

        $this->clientFormValidator->validate('create', $input);

        if (request()->input('columns', [])) {
            ObjectsListSettingsFormValidator::validate('update', request()->all(['columns', 'groupby']));
        }

        if (hasLimit()) {
            $objects_limit = Auth::User()->devices_limit - getManagerUsedLimit(Auth::User()->id);
            if ($objects_limit < $input['devices_limit']) {
                throw new ValidationException(['devices_limit' => trans('front.devices_limit_reached')]);
            }
        }

        if ( ! empty($input['objects'])) {
            if (array_key_exists('devices_limit', $input) && $input['devices_limit'] < count($input['objects'])) {
                throw new DeviceLimitException();
            }
        }

        $plan = array_key_exists('billing_plan_id', $input)
            ? $billingPlanRepo->find($input['billing_plan_id'])
            : null;

        if ( ! empty($plan)) {
            $input['devices_limit'] = $plan->objects;

            if (empty($input['subscription_expiration'])) {
                $input['subscription_expiration'] = date('Y-m-d H:i:s',
                    strtotime(date('Y-m-d H:i:s') . " + {$plan->duration_value} {$plan->duration_type}"));
            }
        }

        beginTransaction();

        try {
            $user = $this->userService->create($input);

            if (empty($input['email_verification'])) {
                $user->markEmailAsVerified();
            }

            if ( ! empty($input['objects'])) {
                $user->devices()->sync($input['objects']);
            }

            if (empty($plan)) {
                if (array_key_exists('perms', $input)) {
                    $permissions = $this->permissionService->getByUser($user, $input['perms']);
                } else {
                    $permissions = $this->permissionService->getUserDefaults();
                }

                $this->userService->setPermissions($user, $permissions);
            }

            if (request()->input('columns', [])) {
                UserRepo::setListViewSettings($user->id, request()->all(['columns', 'groupby']));
            }

            if ($this->user->can('edit', $user, 'custom_fields')) {
                $customValues = $input['custom_fields'] ?? null;
                $this->customValueService->saveCustomValues($user, $customValues);
            }

            commitTransaction();
        } catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }

        if ( ! empty($input['account_created'])) {
            $this->notifyUser($input, 'account_created');
        }

        return Response::json($this->api ? ['status' => 1, 'item' => $user] : ['status' => 1]);
    }

    public function edit($id = null, BillingPlan $billingPlanRepo)
    {
        $item = UserRepo::find($id);

        $this->checkException('users', 'edit', $item);

        $managers = UserRepo::getOtherManagers($item->id)
            ->pluck('email','id')
            ->prepend('-- ' . trans('admin.select') . ' --', '0')
            ->all();
        $maps = getAvailableMaps();
        $plans = [];

        if (settings('main_settings.enable_plans')) {
            $plans = $billingPlanRepo->getWhere([], 'objects', 'asc')
                ->pluck('title', 'id')
                ->prepend('-- ' . trans('admin.select') . ' --', '0')
                ->all();
        }

        $objects_limit = null;

        if (hasLimit()) {
            $objects_limit = $this->user->devices_limit - getManagerUsedLimit($this->user->id, $item->id);
            $objects_limit = $objects_limit < 0 ? 0 : $objects_limit;
        }

        $numeric_sensors = config('tobuli.numeric_sensors');
        $settings = UserRepo::getListViewSettings($id);
        $fields = config('tobuli.listview_fields');
        listviewTrans($id, $settings, $fields);
        $devices = groupDevices($this->user->accessibleDevicesWithGroups()->get(), $this->user);
        $grouped_permissions = $this->permissionService->group(
            $this->permissionService->getByUser($item)
        );
        $permission_values = $item->getPermissions();

        return View::make('admin::' . ucfirst($this->section) . '.edit')->with(compact('item', 'permission_values', 'managers', 'maps', 'plans', 'objects_limit', 'grouped_permissions', 'devices', 'fields', 'settings', 'numeric_sensors'));
    }

    public function update(BillingPlan $billingPlanRepo)
    {
        $input = Input::all();
        $id = $input['id'];
        $item = UserRepo::find($id);

        $this->checkException('users', 'update', $item);

        if (config('app.server') == 'demo' && $item->isGod() && ! Auth::User()->isGod()) {
            return Response::json(['errors' => ['id' => "Can't edit main admin account."]]);
        }

        if (hasLimit()) {
            $input['enable_devices_limit'] = 1;
        }

        if (isset($input['enable_devices_limit']) && empty($input['devices_limit'])) {
            throw new ValidationException([
                'devices_limit' => strtr(trans('validation.required'),
                    [':attribute' => trans('validation.attributes.devices_limit')]),
            ]);
        }

        if (isset($input['enable_expiration_date']) && empty($input['expiration_date'])) {
            throw new ValidationException([
                'expiration_date' => strtr(trans('validation.required'),
                    [':attribute' => trans('validation.attributes.expiration_date')]),
            ]);
        }

        if (isset($input['expiration_date'])) {
            $input['subscription_expiration'] = $input['expiration_date'];
        }

        if (!empty($input['password_generate'])) {
            $input['password'] = $input['password_confirmation'] = Password::generate();
        }

        $this->clientFormValidator->validate('update', $input, $id);

        if (empty($input['password'])) {
            unset($input['password']);
        }

        if (empty($input['manager_id'])) {
            if (isAdmin()) {
                $input['manager_id'] = null;
            } else {
                unset($input['manager_id']);
            }
        }

        if ($id == Auth::User()->id) {
            unset($input['manager_id'], $input['group_id']);
        }

        if ( ! empty($input['manager_id']) && $this->managerInfinity($item, $input['manager_id'])) {
            throw new ValidationException([
                'manager_id' => 'Managers infinity loop'
            ]);
        }

        beginTransaction();

        try {
            if (request()->input('columns', [])) {
                ObjectsListSettingsFormValidator::validate('update', request()->all(['columns', 'groupby']));

                UserRepo::setListViewSettings($id, request()->all(['columns', 'groupby']));
            }

            DB::table('user_permissions')->where('user_id', '=', $item->id)->delete();
            $plan = null;

            if (array_key_exists('billing_plan_id', $input)) {
                $plan = $billingPlanRepo->find($input['billing_plan_id']);

                if (!empty($plan)) {
                    $input['devices_limit'] = $plan->objects;

                    if (empty($input['subscription_expiration'])) {
                        $input['subscription_expiration'] = date('Y-m-d H:i:s',
                            strtotime(date('Y-m-d H:i:s') . " + {$plan->duration_value} {$plan->duration_type}"));
                    }
                }
            }

            if (empty($plan)) {
                $input['billing_plan_id'] = null;
                $input['devices_limit'] = !isset($input['enable_devices_limit']) ? null : $input['devices_limit'];
                $input['subscription_expiration'] = !isset($input['enable_expiration_date']) ? '0000-00-00 00:00:00' : $input['expiration_date'];
            }

            if (Auth::User()->isManager() && Auth::User()->id == $item->id) {
                $input['billing_plan_id'] = $item->billing_plan_id;
                $input['devices_limit'] = $item->devices_limit;
                $input['subscription_expiration'] = $item->subscription_expiration;
            } else {
                if (array_key_exists('perms', $input)) {
                    $permissions = $this->permissionService->getByUser($item, $input['perms']);
                    $this->userService->setPermissions($item, $permissions);
                }
            }

            if (hasLimit()) {
                $objects_limit = Auth::User()->devices_limit - getManagerUsedLimit(Auth::User()->id, $item->id);

                if ($objects_limit < $input['devices_limit'] && $input['devices_limit'] > $item->devices_limit) {
                    throw new ValidationException(['devices_limit' => trans('front.devices_limit_reached')]);
                }
            }

            if (!empty($input['objects'])) {
                if (!is_null($input['devices_limit']) && $input['devices_limit'] < count($input['objects'])) {
                    throw new DeviceLimitException();
                }
            }

            if (isset($input['objects']) && empty($input['objects'])) {
                $input['objects'] = [];
            }

            $input['active'] = isset($input['active']);

            UserRepo::update($id, $input);

            if (isset($input['objects'])) {
                $item->devices()->sync($input['objects']);
            }

            if ($this->user->can('edit', $item, 'custom_fields')) {
                $customValues = $input['custom_fields'] ?? null;
                $this->customValueService->saveCustomValues($item, $customValues);
            }

            commitTransaction();
        } catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }

        if (!empty($input['password'])) {
            $this->notifyUser($input, 'account_password_changed');
        }

        return Response::json(['status' => 1]);
    }


    public function importPoi(User $userRepo)
    {
        $users = $userRepo->getUsers(Auth::User());

        $icons = MapIcon::all();

        return View::make('admin::' . ucfirst($this->section) . '.import_poi')->with(compact('users', 'icons'));
    }

    public function importPoiSet(User $userRepo, POIImportManager $importManager)
    {
        $this->checkException('poi', 'store');

        $validator = Validator::make(request()->all(), [
            'file'       => 'required',
            'map_icon_id'=> 'required',
            'user_id'    => 'required|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $file = request()->file('file');

        if ( ! $file->isValid()) {
            throw new \Exception(trans('front.unsupported_format'));
        }


        $users = $userRepo->getWhereIn(request()->get('user_id'));

        if (empty($users)) {
            return response()->json(['status' => 0]);
        }

        foreach ($users as $user) {
            $additionals = [
                'map_icon_id' => request()->get('map_icon_id'),
                'user_id'     => $user->id
            ];
            $importManager->import($file, $additionals);
        }

        return response()->json([
            'status' => 1,
            'message' => trans('front.successfully_saved'),
        ]);
    }

    public function importGeofences(User $userRepo)
    {
        $users = $userRepo->getUsers(Auth::User());

        return View::make('admin::' . ucfirst($this->section) . '.import_geofences')->with(compact('users'));
    }

    public function importGeofencesSet(User $userRepo, GeofenceImportManager $importManager) {
        $this->checkException('geofences', 'store');

        $validator = Validator::make(request()->all(), [
            'user_id'    => 'required|array',
            'file'       => 'required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $file = request()->file('file');

        if ( ! $file->isValid()) {
            throw new \Exception(trans('front.unsupported_format'));
        }

        $users = $userRepo->getWhereIn(request()->get('user_id'));

        if (empty($users)) {
            return response()->json(['status' => 0]);
        }

        foreach ($users as $user) {
            $additionals = [
                'user_id'     => $user->id
            ];
            $importManager->import($file, $additionals);
        }

        return response()->json([
            'status' => 1,
            'message' => trans('front.successfully_saved'),
        ]);
    }

    public function importRoutes(User $userRepo)
    {
        $users = $userRepo->getUsers(Auth::User());

        return View::make('admin::' . ucfirst($this->section) . '.import_routes')->with(compact('users'));
    }

    public function importRoutesSet(User $userRepo, RouteImportManager $importManager) {
        $this->checkException('routes', 'store');

        $validator = Validator::make(request()->all(), [
            'user_id'    => 'required|array',
            'file'       => 'required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $file = request()->file('file');

        if ( ! $file->isValid()) {
            throw new \Exception(trans('front.unsupported_format'));
        }

        $users = $userRepo->getWhereIn(request()->get('user_id'));

        if (empty($users)) {
            return response()->json(['status' => 0]);
        }

        foreach ($users as $user) {
            $additionals = [
                'user_id'     => $user->id
            ];
            $importManager->import($file, $additionals);
        }

        return response()->json([
            'status' => 1,
            'message' => trans('front.successfully_saved'),
        ]);
    }

    public function getDevices($id)
    {
        $user = UserRepo::getWithFirst(['devices', 'devices.traccar'], ['id' => $id]);

        $this->checkException('users', 'show', $user);

        $items = $user->devices;

        return View::make('admin::Clients.get_devices')->with(compact('items'));
    }

    public function doDestroy()
    {
        return view('admin::' . ucfirst($this->section) . '.destroy',  ['ids' => request('id')]);
    }

    public function destroy($id = null)
    {
        $ids = Input::get('ids', $id);

        if (empty($ids)) {
            return Response::json(['status' => 1]);
        }

        if ( ! is_array($ids)) {
            $ids = [$ids];
        }

        $users = \Tobuli\Entities\User::whereIn('id', $ids)->get();

        foreach ($users as $user) {
            if ( ! $this->user->can('remove', $user)) {
                continue;
            }

            $user->delete();
        }

        return Response::json(['status' => 1]);
    }

    public function loginAs($id)
    {
        $item = UserRepo::find($id);

        $this->checkException('users', 'show', $item);

        return View::make('admin::Clients.login_as')->with(compact('item'));
    }

    public function loginAsAgree($id)
    {
        $item = UserRepo::find($id);

        $this->checkException('users', 'show', $item);

        if ( ! empty($item)) {
            session()->put('previous_user', Auth::user()->id);
            auth()->loginUsingId($item->id);
        }

        return Redirect::route('home');
    }

    public function getPermissionsTable(BillingPlan $billingPlanRepo, User $userRepo)
    {
        $user = $userRepo->find(request('user_id'));
        $plan = $billingPlanRepo->find(request('id'));

        if ( ! is_null($user)) {
            $this->checkException('users', 'show', $user);
            $permissions = $this->permissionService->getByUser($user);
        } else {
            $permissions = (request()->filled('group_id')) ?
                $this->permissionService->getByGroupId(request('group_id')) :
                $this->permissionService->getByUserRole();
        }

        $is_plan_set = ( ! is_null($plan));

        $item = $is_plan_set ? $plan : $user;

        if ( ! is_null($item)) {
            $permission_values = $item->getPermissions();
        } else {
            $permission_values = (request()->filled('group_id')) ?
                $this->permissionService->getGroupDefaults(request('group_id')) :
                $this->permissionService->getUserDefaults();
        }

        return view('Admin.Clients._perms')->with([
            'permission_values'  => $permission_values,
            'plan'    => $is_plan_set,
            'grouped_permissions' => $this->permissionService->group($permissions),
        ]);
    }

    private function notifyUser(array $data, string $templateName)
    {
        $template = EmailTemplate::getTemplate($templateName, $this->user);

        try {
            sendTemplateEmail($data['email'], $template, $data);
        } catch (\Exception $e) {
            throw new ValidationException(['id' => 'Failed to send notify mail. Check email settings.']);
        }
    }

    public function setStatus()
    {
        $validator = Validator::make(request()->all(), [
            'id'     => 'required_without:email',
            'email'  => 'required_without:id|email',
            'status' => 'required|in:1,0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->messages());
        }

        if (request()->filled('id')) {
            $user = \Tobuli\Entities\User::find(request('id'));
        } else {
            $user = \Tobuli\Entities\User::where('email', request('email'))->first();
        }

        $this->checkException('users', 'edit', $user);

        $user->update(['active' => request('status')]);

        return Response::json(['status' => 1]);
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

        \Tobuli\Entities\User::accessibleUsers($this->user)
            ->whereIn('id', $this->data['id'])
            ->update(['active' => $this->data['active']]);

        return Response::json(['status' => 1]);
    }

    private function managerInfinity($user, $manager_id, $managers = [])
    {
        // User cant be his own manager
        if ($manager_id == $user->id) {
            return true;
        }

        $manager = \Tobuli\Entities\User::find($manager_id);

        if ( ! $manager) {
            return false;
        }

        if ( ! $manager->manager_id) {
            return false;
        }

        // Managers infinity loop
        if (in_array($manager->id, $managers)) {
            return true;
        }

        $managers[] = $manager->id;

        return $this->managerInfinity($user, $manager->manager_id, $managers);
    }

    //////// NOVAS FUNCÇÕES

 

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
 

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
 
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
 
}
