<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;

use App\Console\ProcessManager;
use Tobuli\Entities\TraccarDevice;

class CleanServerCommand extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'server:clean';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';


	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
        $this->processManager = new ProcessManager($this->name, $timeout = 3600, $limit = 1);

        if (!$this->processManager->canProcess())
        {
            echo "Cant process \n";
            return false;
        }

		$date = $this->argument('date').' 00:00:00';

		$devices = TraccarDevice::orderBy('id', 'asc')->get();
		$all = count($devices);
		$i = 1;

		foreach ($devices as $device)
        {
            try {
                $device->positions()->where('time', '<', $date)->delete();
            } catch (\Exception $e) {}

			$this->line("CLEAN TABLES ({$i}/{$all})\n");
			$i++;
		}

		$this->line("Job done[OK]\n");
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('date', InputArgument::REQUIRED, 'The date')
		);
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
