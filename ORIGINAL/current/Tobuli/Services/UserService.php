<?php namespace Tobuli\Services;

use CustomFacades\Appearance;
use CustomFacades\Repositories\BillingPlanRepo;
use CustomFacades\Repositories\UserRepo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tobuli\Entities\User;

class UserService
{
    /**
     * @var PermissionService
     */
    private $permissionService;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
    }

    /**
     * @return array
     */
    public function getDefaults()
    {

        return [
            'active'           => true,
            'group_id'         => 2,
            'lang'             => Appearance::getSetting('default_language'),
            'unit_of_altitude' => Appearance::getSetting('default_unit_of_altitude'),
            'unit_of_distance' => Appearance::getSetting('default_unit_of_distance'),
            'unit_of_capacity' => Appearance::getSetting('default_unit_of_capacity'),
            'map_id'           => settings('main_settings.default_map'),
            'timezone_id'      => settings('main_settings.default_timezone'),
            'dst_date_from'    => settings('main_settings.dst') ? settings('main_settings.dst_date_from') : null,
            'dst_date_to'      => settings('main_settings.dst') ? settings('main_settings.dst_date_from') : null,

            'available_maps'   => settings('main_settings.available_maps'),
            'devices_limit' => null,
            'subscription_expiration' => '0000-00-00 00:00:00',
            'open_device_groups' => '["0"]',
            'open_geofence_groups' => '["0"]',
        ];
    }

    /**
     * @param array $data
     * @return User
     */
    public function create(array $data)
    {
        $data = array_merge($this->getDefaults(), $data);

        $user = UserRepo::create($data);
        $user->setDefaultTimezone();

        return $user;
    }

    /**
     * @param User $user
     * @param array $permissions
     */
    public function setPermissions(User $user, array $permissions)
    {
        DB::table('user_permissions')->where(['user_id' => $user->id])->delete();

        foreach ($permissions as $key => $val) {
            DB::table('user_permissions')->insert([
                'user_id' => $user->id,
                'name'    => $key,
                'view'    => $val['view'],
                'edit'    => $val['edit'],
                'remove'  => $val['remove'],
            ]);
        }
    }

    /**
     * @param array $data
     * @return User
     */
    public function registration(array $data) {
        if (settings('main_settings.enable_plans') && settings('main_settings.default_billing_plan')) {
            $plan = BillingPlanRepo::find(settings('main_settings.default_billing_plan'));
            $data['devices_limit'] = $plan->objects;
            $data['billing_plan_id'] = settings('main_settings.default_billing_plan');

            if ($plan->price)
                $expiration = date('Y-m-d H:i:s');
            else
                $expiration = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')." + {$plan->duration_value} {$plan->duration_type}"));

            $data['subscription_expiration'] = $expiration;
        } else {
            $expiration_days = settings('main_settings.subscription_expiration_after_days');
            $data['subscription_expiration'] = is_null($expiration_days)
                ? null
                : date('Y-m-d H:i:s',strtotime('+'.$expiration_days.' days'));
            $data['devices_limit'] = settings('main_settings.devices_limit');
        }

        $data['manager_id'] = NULL;
        if (Session::has('referer_id')) {
            $user = UserRepo::find(Session::get('referer_id'));
            if (!empty($user) && $user->isManager())
                $data['manager_id'] = $user->id;
        }

        $user = $this->create($data + ['group_id' => 2]);

        if (!(settings('main_settings.enable_plans') && settings('main_settings.default_billing_plan'))) {
            $permissions = $this->permissionService->getUserDefaults();
            $this->setPermissions($user, $permissions);
        }

        $user->setDefaultTimezone();

        return $user;
    }

    /**
     * @return string
     */
    public function generatePassword()
    {
        return str_random(12);
    }
}