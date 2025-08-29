<?php namespace App\Console\Commands;

use Formatter;
use GuzzleHttp\Exception\ClientException;
use Carbon\Carbon;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Device;
use Tobuli\Entities\EmailTemplate;
use Tobuli\Entities\SendQueue;
use Tobuli\Entities\SmsTemplate;
use Bugsnag\BugsnagLaravel\BugsnagFacade as Bugsnag;
use App\Console\ProcessManager;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\Commands\SendCommandService;
use Tobuli\Services\FcmService;
use Tobuli\Services\SharingService;

class SendEventsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'events:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check event queue(send notifications and clear).';

    private $templates = [];

    private $sendCommandsService;

    private $sharingService;

    private $fcmService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SharingService $sharingService)
    {
        parent::__construct();

        $this->fcmService = new FcmService();
        $this->sendCommandsService = new SendCommandService();
        $this->sharingService = $sharingService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->processManager = new ProcessManager($this->name, $timeout = 120, $limit = 2);

        if ( ! $this->processManager->canProcess()) {
            echo "Can't process \n";
            return false;
        }

        DB::disableQueryLog();

        while ($this->processManager->canContinue()) {
            $items = SendQueue::with(['user'])->orderBy('id', 'asc')->take(100)->get();

            foreach ($items as $item) {
                if ( ! $this->processManager->lock($item->id))
                    continue;

                if (empty($item->channels)) {
                    $item->delete();
                    continue;
                }

                if ($item->user)
                    setActingUser($item->user);


                foreach ($item->channels as $channel => $receiver)
                    $this->toChannel($channel, $receiver, $item);

                $item->delete();
            }
            sleep(1);
        }

        return 'DONE';
    }

    private function toChannel($channel, $receiver, $sendQueue)
    {
        if (empty($receiver))
            return;

        $device = $sendQueue->data instanceof Device ? $sendQueue->data : $sendQueue->data->device;

        try {
            switch ($channel) {
                case 'push':
                    switch ($sendQueue->type) {
                        case 'expiring_user':
                        case 'expired_user':
                            $title = $sendQueue->data->email;
                            $body = '';
                            break;
                        case 'expiring_device':
                        case 'expired_device':
                            $title = $sendQueue->data->name;
                            $body = '';
                            break;
                        default:
                            $title = $device->name . ' ' . $sendQueue->data->message;
                            $body = trans('front.speed') . ': ' . Formatter::speed()->human($sendQueue->data->speed);

                            if (in_array($sendQueue->type, ['zone_out', 'zone_in'])) {
                                $body .= "\n" . trans('front.geofence') . ': ' . $sendQueue->data->geofence->name;

                                $sendQueue->data->makeHidden('geofence');
                            }
                            break;
                    }

                    $data = $sendQueue->data ? $sendQueue->data->toArray() : [];

                    $this->fcmService->send($sendQueue->user, $title, $body, $data);
                    break;
                case 'email':
                    $template = $this->getTemplate('email', $sendQueue->type, $sendQueue->user);

                    sendTemplateEmail($receiver, $template, $sendQueue->data);
                    break;
                case 'mobile_phone':
                    $template = $this->getTemplate('sms', $sendQueue->type, $sendQueue->user);

                    sendTemplateSMS($receiver, $template, $sendQueue->data, $sendQueue->user);
                    break;
                case 'webhook':
                    $data = $sendQueue->data->toArray();
                    $data['user'] = [
                        'id'    => $sendQueue->user->id,
                        'email' => $sendQueue->user->email,
                        'phone_number' => $sendQueue->user->phone_number,
                    ];

                    if (!empty($data['latitude']) && !empty($data['longitude']))
                        $data['address'] = getGeoAddress($data['latitude'], $data['longitude']);

                    $data['device'] = $device->toArray();
                    $data['geofence'] = $sendQueue->data->geofence;
                    $data['sensors'] = $device->sensors->map(function ($sensor, $key) use ($device) {
                        $value = $sensor->getValueCurrent($device->other);

                        return [
                            'id'        => (int)$sensor->id,
                            'type'      => $sensor->type,
                            'name'      => $sensor->formatName(),
                            'value'     => $value,
                            'unit'      => $sensor->getUnit(),
                            'formatted' => $sensor->formatValue($value),
                        ];
                    })->all();

                    unset($data['device']['traccar']);

                    sendWebhook($receiver, $data);
                    break;
                case 'command':
                    if ($sendQueue->user->perm('send_command', 'view')) {
                        $command = $receiver;
                        $this->sendCommandsService->gprs($device, $command, $sendQueue->user);
                    }
                    break;
                case 'sharing_email':
                case 'sharing_sms':

                    $plugin = settings('plugins.alert_sharing.options');

                    $sharingData = [
                        'expiration_date'         => null,
                        'delete_after_expiration' => array_get($plugin, 'delete_after_expiration.status')
                    ];

                    if (array_get($plugin, 'duration.active') && array_get($plugin, 'duration.value')) {
                        $sharingData['expiration_date'] = Carbon::now()->addMinutes(array_get($plugin,
                            'duration.value'));
                    }


                    $sharing = $this->sharingService->create($sendQueue->user_id, $sharingData);

                    $this->sharingService->addDevices($sharing, $device);

                    if ($channel == 'sharing_email') {
                        $this->sharingService->sendEmail($sharing, $receiver);
                    }

                    if ($channel == 'sharing_sms') {
                        $this->sharingService->sendSms($sharing, $receiver);
                    }
                    break;
            }
        } catch (ConnectException $e) {
        } catch (ClientException $e) {
        } catch (ServerException $e) {
        } catch (ValidationException $e) {
            //echo "ValidationException " . json_encode($e->getErrors()) . " \n";
        }
        catch (\Exception $e) {
            //echo "Exception {$e->getMessage()} \n";
            Bugsnag::notifyException($e);
        }
    }

    private function getTemplate($type, $name, $user, $depth = 0)
    {
        if ($depth > 1)
            throw new \Exception("getTemplate recursive: '$type', '$name', '$depth'");

        switch ($type) {
            case 'email':
                $template = EmailTemplate::getTemplate($name, $user);
                break;
            case 'sms':
                $template = SmsTemplate::getTemplate($name, $user);
                break;
            default:
                throw new \Exception("Wrong template type '$type'");
        }

        if (empty($template))
            $template = $this->getTemplate($type, 'event', $user, ++$depth);

        return $template;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }
}