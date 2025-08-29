<?php namespace App\Console\Commands;

set_time_limit(0);

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use App\Console\ProcessManager;
use Tobuli\Entities\Database;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarDevice;

class CopyDevicesCommand extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'positions:copy';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Devices positions copy';

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

        if ( ! $this->processManager->canProcess())
        {
            echo "Cant process \n";
            return false;
        }

        $query = Device::query();
        $this->displayFilterCount($query);

        $this->filterId($query);
        $this->displayFilterCount($query);

        $this->filterConnectionTime($query);
        $this->displayFilterCount($query);

        $this->filterPositionDatabase($query);
        $this->displayFilterCount($query);

        $database_id = $this->askDatabaseCopyTo();

        $this->filterCurrentDatabase($query, $database_id);
        $this->displayFilterCount($query);

        $this->filterLimit($query);

        $devices = $query->get();

        $bar = $this->output->createProgressBar(count($devices));

        foreach ($devices as $device) {
            $this->performTask($device, $database_id);

            $bar->advance();
        }

        $bar->finish();

		$this->line("Job done[OK]\n");
	}

    protected function performTask($device, $database_id) {
        try {
            $device->traccar->copyTo($database_id);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function askDatabaseCopyTo()
    {
        $databases = $this->getDatabases();
        $choises = [];

        foreach ($databases as $database) {
            if ($database->id)
                $count = TraccarDevice::where('database_id', $database->id)->count();
            else
                $count = TraccarDevice::whereNull('database_id')->count();

            $choises[$database->id] = "{$database->host} (Count: $count)";
        }

        $choise = $this->choice("Copy to database:", $choises, 0);

        $database_id = array_search($choise, $choises);

        return $database_id ? $database_id : null;
    }

    protected function filterId(&$query) {
        $do = $this->choice('Filter by id?', ['No', 'Yes']);

        if (strtolower($do) == 'no')
            return;

        $id = $this->ask("Filter ID from:");

        if ($id)
            $query->where('id', '>=', $id);

        $id = $this->ask("Filter ID to:");

        if ($id)
            $query->where('id', '<=', $id);
    }

    protected function filterConnectionTime(&$query) {
        $do = $this->choice('Filter by connection time?', ['No', 'Yes']);

        if (strtolower($do) == 'no')
            return;

        $time = $this->ask("Filter last connection time before (Y-m-d H:i:s):");

        if ($time)
            $query->connectedBefore($time);

        $time = $this->ask("Filter last connection time after (Y-m-d H:i:s):");

        if ($time)
            $query->connectedAfter($time);
    }

    protected function filterPositionDatabase(&$query) {
        $do = $this->choice('Filter by position database?', ['No', 'Yes']);

        if (strtolower($do) == 'no')
            return;

        $databases = $this->getDatabases();

        $choises = [];

        foreach ($databases as $database) {
            if ($database->id)
                $count = TraccarDevice::where('database_id', $database->id)->count();
            else
                $count = TraccarDevice::whereNull('database_id')->count();

            $choises[$database->id] = "{$database->host} (Count: $count)";
        }

        $choise = $this->choice("Filter position database:", $choises, 0);

        $database_id = array_search($choise, $choises);

        $query->traccarJoin();

        if ($database_id)
            $query->where('traccar.database_id', $database_id);
        else
            $query->whereNull('traccar.database_id');
    }

    protected function filterLimit(&$query) {
        $do = $this->choice('Filter by limit?', ['No', 'Yes']);

        if (strtolower($do) == 'no')
            return;

        $limit = $this->ask("Filter limit:");

        if ($limit)
            $query->limit($limit);
    }

    protected function filterCurrentDatabase(&$query, $database_id)
    {
        $hasDatabaseFilter = array_first($query->getQuery()->wheres, function($where){
            return array_get($where, 'column') == 'traccar.database_id';
        });

        if ($hasDatabaseFilter)
            return;

        $query->traccarJoin();

        if (empty($database_id)) {
            $query->whereNotNull('traccar.database_id');
        } else {
            $query->where('traccar.database_id', '!=', $database_id);
        }
    }

    protected function displayFilterCount($query) {
        $this->info("Devices selected: " . (clone $query)->count());
    }

    protected function getDatabases()
    {
        $defaultDatabase = new Database();
        $defaultDatabase->id = 0;
        $defaultDatabase->host = 'localhost';

        return Database::all()->prepend($defaultDatabase);
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
