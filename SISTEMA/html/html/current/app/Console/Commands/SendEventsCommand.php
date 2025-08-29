<?php namespace App\Console\Commands;

use App\Console\ProcessManager;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag as Bugsnag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputArgument;
use Tobuli\Entities\SendQueue;
use Tobuli\Helpers\Alerts\Notification\Send\SendException;
use Tobuli\Helpers\Alerts\Notification\Send\SendingInterface;
use Tobuli\Helpers\Alerts\NotificationProvider;

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

    private NotificationProvider $notificationProvider;

    protected $debug;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->notificationProvider = (new NotificationProvider())->clearFilters();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->debug = ! empty($this->argument('debug'));

        $take = 50;
        $timeout = config('tobuli.process.send_event_timeout');
        $limit = config('tobuli.process.send_event_limit');
        $this->processManager = new ProcessManager($this->name, $timeout, $limit);

        if ( ! $this->processManager->canProcess()) {
            $this->line("Can't process");
            return self::INVALID;
        }

        DB::disableQueryLog();

        while ($this->processManager->canContinue()) {
            $items = SendQueue::with(['user'])->orderBy('id', 'asc')->take($take)->get();

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

            if ($items->count() < $take)
                sleep(1);
        }

        $this->line("DONE");

        return self::SUCCESS;
    }

    private function toChannel($channel, $receiver, $sendQueue)
    {
        if (empty($receiver))
            return;

        $notification = $this->notificationProvider->find($channel);

        if (!$notification instanceof SendingInterface) {
            return;
        }

        if (!$notification->canSend($sendQueue)) {
            return;
        }

        try {
            $notification->send($sendQueue, $receiver);
        } catch (SendException $e) {
            if ($this->debug) {
                $this->error($e->getMessage());
            }
        }
        catch (\Exception $e) {
            if ($this->debug) {
                $this->error($e->getMessage());
            }
            Bugsnag::notifyException($e);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['debug', InputArgument::OPTIONAL, 'Debug']
        ];
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