<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tobuli\Entities\Device;
use Tobuli\Repositories\Config\ConfigRepositoryInterface as Config;


class CalcVirtualOdometerCommand extends Command {
    /**
     * @var Config
     */
    private $config;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'virtual_odometer:calc';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';
    /**
     * Create a new command instance.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct();

        $this->config = $config;
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = $this->config->whereTitle('alerts_last_check');
        $this->config->update($config->id, ['value' => time()]);
        $time = date('Y-m-d H:i:s', $config->value);

        $devices = Device::connectedAfter($time)
            ->whereHas('sensors', function($query){
                $query->where('type', 'odometer');
                $query->where('odometer_value_by', 'virtual_odometer');
            })
            ->with(['sensors' => function($query) {
                $query->where('type', 'odometer');
                $query->where('odometer_value_by', 'virtual_odometer');
            }])
            ->get();

        foreach ($devices as $device) {
            $this->process($device, $time);
        }

        $this->line("DONE");
    }

    protected function process($device, $time)
    {
        static $select = ['id', 'latitude', 'longitude', 'time', 'distance'];

        $positions = $device->positions()
            ->select($select)
            ->union(
                $device->positions()
                    ->select($select)
                    ->where('server_time', '<=', $time)
                    ->orderliness()
                    ->limit(1)
            )
            ->where('server_time', '>', $time)
            ->orderliness()
            ->get();

        $previous = $positions->shift();

        foreach ($positions as &$position) {

            $distance = getDistance($position->latitude, $position->longitude, $previous->latitude, $previous->longitude);

            if (round($distance, 5) != round($position->distance, 5)) {
                $position->distance = $distance;

                $device->positions()->whereId($position->id)->update(['distance' => $distance]);
            }

            $previous = $position;
        }

        $distance = $positions->sum('distance');

        if (!$distance)
            return;

        foreach ($device->sensors as $sensor) {
            $sensor->update([
                'odometer_value' => $sensor->getOriginal('odometer_value') + $distance
            ], ['timestamps' => false]);
        }

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