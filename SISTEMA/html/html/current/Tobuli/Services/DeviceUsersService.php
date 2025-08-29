<?php

namespace Tobuli\Services;

use App\Exceptions\DeviceLimitException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Entities\User;
use CustomFacades\Server;
use Tobuli\Services\EntityLoader\UsersLoader;

class DeviceUsersService
{
    /**
     * @var User
     */
    protected $user;

    public function __construct()
    {

    }

    public function setActingUser(User $user)
    {
        $this->user = $user;
    }

    public function getActingUser()
    {
        if (is_null($this->user))
            return getActingUser();

        return $this->user;
    }

    /**
     * @param User $user
     * @param Collection|Device[]|int[]|Device|int $devices
     * @param bool $visible
     * @return int
     */
    public function setVisibleDevices(User $user, $devices, $visible)
    {
        return DB::table('user_device_pivot')
            ->where('user_id', $user->id)
            ->whereIn('device_id', $this->resolveDevices($devices))
            ->update([
                'active' => $visible
            ]);
    }

    /**
     * @param User $user
     * @param Collection|DeviceGroup[]|int[]|DeviceGroup|int $groups
     * @param bool $visible
     * @return int
     */
    public function setVisibleGroups(User $user, $groups, $visible)
    {
        return DB::table('user_device_pivot')
            ->where('user_id', $user->id)
            ->whereIn('group_id', $this->resolveGroups($groups))
            ->update([
                'active' => $visible
            ]);
    }

    /**
     * @param Device $device
     * @param User|int $user
     * @param DeviceGroup|int|null $group
     */
    public function addUser(Device $device, $user, $group = 0) {
        $user_id = $this->resolveUser($user);
        $group_id = $this->resolveGroup($group);

        $device->users()->sync([
            $user_id => [
                'group_id' => $group_id
            ]
        ], false);
    }

    /**
     * @param Device $device
     * @param User|int $user
     */
    public function removeUser(Device $device, $user) {
        $user_id = $this->resolveUser($user);

        $device->users()->detach($user_id);
    }

    /**
     * @param Device $device
     * @param UsersLoader|Collection|User[]|int[]|User|int $users
     */
    public function syncUsers(Device $device, $users)
    {
        $query = $device->users()
            ->wherePivotIn('user_id', function ($query) {
                $userActing = $this->getActingUser();

                $query
                    ->select('users.id')
                    ->from('users');

                switch (true) {
                    case $userActing->isAdmin():
                    case $userActing->isSupervisor():
                        break;
                    case $userActing->isManager():
                        $query->where(function($q) use ($userActing) {
                            return $q->where('manager_id', $userActing->id)->orWhere('id', $userActing->id);
                        });
                        break;
                    default:
                        $query->where('id', $userActing->id);
                }

                if (!$userActing->isGod()) {
                    $query->where('users.email', '!=', 'admin@server.com');
                }

                return $query;
            });

        if ($users instanceof UsersLoader) {
            $query->syncLoader($users);
        } else {
            $users = $this->resolveUsers($users);
            $query->sync($users);
        }

    }

    /**
     * @param Device $device
     * @param Collection|User[]|int[]|User|int $users
     * @param DeviceGroup|int|null $group
     * @param bool|null $visible
     */
    public function setGroup(Device $device, $users, $group, $visible = null)
    {
        $data = [
            'group_id' => $this->resolveGroup($group)
        ];

        if (!is_null($visible)) {
            $data['active'] = $visible;
        }

        \DB::table('user_device_pivot')
            ->where('device_id', $device->id)
            ->whereIn('user_id', $this->resolveUsers($users))
            ->update($data);
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public function isLimitReached($user = NULL)
    {
        if ($this->isServerLimitReached()) {
            return true;
        }

        if ($user && $this->isUserLimitReached($user)) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isUserLimitReached(User $user, $fully = true)
    {
        if (!$user->hasDeviceLimit())
            return false;

        $user_devices_count = $this->getUsedLimit($user);

        if ($fully) {
            return $user_devices_count >= $user->devices_limit;
        }

        return $user_devices_count > $user->devices_limit;
    }

    /**
     * @return bool
     */
    public function isServerLimitReached()
    {
        if (!Server::hasDeviceLimit()) {
            return false;
        }

        return Server::getDeviceLimit() <= $count = Device::count();
    }

    /**
     * @param User $user
     * @return int
     */
    public function getUsedLimit(User $user)
    {
        if ($user->isManager()) {
            return $this->getManagerUsedLimit($user);
        }

        return $user->devices()->count();
    }

    /**
     * @param User $manager
     * @param User|null $except
     * @return int
     */
    public function getManagerFreeLimit(User $manager, User $except = NULL)
    {
        $free_limit = $manager->devices_limit - $this->getManagerUsedLimit($manager, $except);

        return $free_limit < 0 ? 0 : $free_limit;
    }

    /**
     * @param User $manager
     * @param User|null $except
     * @return int
     */
    public function getManagerUsedLimit(User $manager, User $except = NULL)
    {
        $users_limit = $manager
            ->subusers()
            ->when($except, function($query) use ($except) {
                $query->where('id', '!=', $except);
            })
            ->sum('devices_limit');

        $manager_limit = $manager->devices()->count();

        return $users_limit + $manager_limit;
    }

    /**
     * @param UsersLoader|Collection|User[]|int[]|User|int $users
     * @param Device|null $device
     * @return mixed
     */
    public function getUsersReachedLimit($users, Device $device = null)
    {
        if ($users instanceof UsersLoader) {
            $query = $users->getQuery();
        } else {
            $query = User::whereIn('id', $this->resolveUsers($users));
        }

        return $query
            ->whereNotNull('devices_limit')
            ->with(['devices' => function($q) use ($device) {
                $q->where('user_device_pivot.device_id', $device ? $device->id : null);
            }])
            ->get()
            ->filter(function($user) {
                $hasThisDevice = !$user->devices->isEmpty();

                return $this->isUserLimitReached($user, !$hasThisDevice);
            });
    }

    /**
     * @param DeviceGroup|int|null $group
     * @return int|mixed|null
     */
    protected function resolveGroup($group)
    {
        if (empty($group))
            return 0;

        return $group instanceof DeviceGroup ? $group->id : (int)$group;
    }

    /**
     * @param Collection|DeviceGroup[]|int[]|DeviceGroup|int $groups
     * @return int|mixed|null
     */
    protected function resolveGroups($groups)
    {
        if (!is_array($groups))
            $groups = [$groups];

        $resolved = [];

        foreach ($groups as $group) {
            $resolved[] = $this->resolveGroup($group);
        }

        return $resolved;
    }

    /**
     * @param Device|int $device
     * @return int
     */
    protected function resolveDevice($device)
    {
        return $device instanceof Device ? $device->id : (int)$device;
    }

    /**
     * @param Collection|Device[]|int[]|Device|int $devices
     * @return int[]
     */
    protected function resolveDevices($devices)
    {
        if (!is_array($devices))
            $devices = [$devices];

        $resolved = [];

        foreach ($devices as $device) {
            $resolved[] = $this->resolveDevice($device);
        }

        return $resolved;
    }

    /**
     * @param User|int $user
     * @return int
     */
    protected function resolveUser($user)
    {
        return $user instanceof User ? $user->id : (int)$user;
    }

    /**
     * @param Collection|User[]|int[]|User|int $users
     * @return int[]
     */
    protected function resolveUsers($users)
    {
        if (!is_array($users))
            $users = [$users];

        $resolved = [];

        foreach ($users as $user) {
            $resolved[] = $this->resolveUser($user);
        }

        return $resolved;
    }
}
