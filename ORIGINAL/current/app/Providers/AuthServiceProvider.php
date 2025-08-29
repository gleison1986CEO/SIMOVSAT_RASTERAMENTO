<?php

namespace App\Providers;

use App\Policies\MediaCategoryPolicy;
use App\Policies\CommandSchedulePolicy;
use App\Policies\DeviceIconPolicy;
use App\Policies\DeviceRouteTypePolicy;
use App\Policies\DriverPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\EventCustomPolicy;
use App\Policies\EventPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PoiGroupPolicy;
use App\Policies\SmsTemplatePolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\UserGprsTemplatePolicy;
use App\Policies\UserPolicy;
use App\Policies\UserSmsTemplatePolicy;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Policies\AlertPolicy;
use App\Policies\ChatPolicy;
use App\Policies\DevicePolicy;
use App\Policies\DeviceGroupPolicy;
use App\Policies\GeofencePolicy;
use App\Policies\GeofenceGroupPolicy;
use App\Policies\PoiPolicy;
use App\Policies\ReportPolicy;
use App\Policies\ReportLogPolicy;
use App\Policies\RoutePolicy;
use App\Policies\TaskPolicy;
use App\Policies\DeviceCameraPolicy;
use App\Policies\SharingPolicy;
use App\Policies\ChecklistTemplatePolicy;
use App\Policies\ChecklistPolicy;
use App\Policies\ChecklistRowPolicy;
use App\Policies\CallActionPolicy;
use App\Policies\CustomFieldPolicy;

use Tobuli\Entities\Alert;
use Tobuli\Entities\MediaCategory;
use Tobuli\Entities\Chat;
use Tobuli\Entities\CommandSchedule;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Entities\DeviceIcon;
use Tobuli\Entities\DeviceRouteType;
use Tobuli\Entities\EmailTemplate;
use Tobuli\Entities\Event;
use Tobuli\Entities\EventCustom;
use Tobuli\Entities\Geofence;
use Tobuli\Entities\GeofenceGroup;
use Tobuli\Entities\Order;
use Tobuli\Entities\PoiGroup;
use Tobuli\Entities\SmsTemplate;
use Tobuli\Entities\Subscription;
use Tobuli\Entities\User;
use Tobuli\Entities\UserDriver;
use Tobuli\Entities\Poi;
use Tobuli\Entities\Report;
use Tobuli\Entities\ReportLog;
use Tobuli\Entities\Route;
use Tobuli\Entities\Task;
use Tobuli\Entities\DeviceCamera;
use Tobuli\Entities\Sharing;
use Tobuli\Entities\ChecklistTemplate;
use Tobuli\Entities\Checklist;
use Tobuli\Entities\ChecklistRow;
use Tobuli\Entities\CallAction;
use Tobuli\Entities\CustomField;
use Tobuli\Entities\UserGprsTemplate;
use Tobuli\Entities\UserSmsTemplate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Alert::class             => AlertPolicy::class,
        Device::class            => DevicePolicy::class,
        DeviceGroup::class       => DeviceGroupPolicy::class,
        DeviceIcon::class        => DeviceIconPolicy::class,
        Geofence::class          => GeofencePolicy::class,
        GeofenceGroup::class     => GeofenceGroupPolicy::class,
        Poi::class               => PoiPolicy::class,
        PoiGroup::class          => PoiGroupPolicy::class,
        Report::class            => ReportPolicy::class,
        ReportLog::class         => ReportLogPolicy::class,
        Route::class             => RoutePolicy::class,
        Chat::class              => ChatPolicy::class,
        Task::class              => TaskPolicy::class,
        EventCustom::class       => EventCustomPolicy::class,
        Event::class             => EventPolicy::class,
        UserDriver::class        => DriverPolicy::class,
        User::class              => UserPolicy::class,
        CommandSchedule::class   => CommandSchedulePolicy::class,
        DeviceCamera::class      => DeviceCameraPolicy::class,
        Sharing::class           => SharingPolicy::class,
        ChecklistTemplate::class => ChecklistTemplatePolicy::class,
        Checklist::class         => ChecklistPolicy::class,
        ChecklistRow::class      => ChecklistRowPolicy::class,
        CallAction::class        => CallActionPolicy::class,
        CustomField::class       => CustomFieldPolicy::class,
        Order::class             => OrderPolicy::class,
        Subscription::class      => SubscriptionPolicy::class,
        EmailTemplate::class     => EmailTemplatePolicy::class,
        SmsTemplate::class       => SmsTemplatePolicy::class,
        UserSmsTemplate::class   => UserSmsTemplatePolicy::class,
        UserGprsTemplate::class  => UserGprsTemplatePolicy::class,
        DeviceRouteType::class   => DeviceRouteTypePolicy::class,
        MediaCategory::class          => MediaCategoryPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
