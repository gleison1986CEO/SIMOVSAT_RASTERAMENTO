<?php namespace Tobuli\Entities;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TraccarDevice extends Eloquent {
    protected $connection = 'traccar_mysql';

	protected $table = 'devices';

    protected $fillable = array(
        'database_id',
        'name',
        'uniqueId',
        'latestPosition_id',
        'lastValidLatitude',
        'lastValidLongitude',
        'device_time',
        'server_time',
        'ack_time',
        'time',
        'speed',
        'other',
        'altitude',
        'power',
        'course',
        'address',
        'protocol',
        'latest_positions'
    );

    public $timestamps = false;

    public function positions()
    {
        $instance = new TraccarPosition();
        $instance->setTable('positions_' . $this->id);

        $foreignKey = $instance->getTable().'.device_id';
        $localKey = 'id';

        if ($connection = $this->getDatabaseName())
            $instance->setConnection($connection);

        return new HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    public function getDatabaseName()
    {
        return Database::getDatabaseName($this->database_id);
    }

    public function copyTo($database_id)
    {
        $connection = $this->getDatabaseName();
        $from = config("database.connections.$connection");
        $to = Database::getDatabase($database_id);
        $table = "positions_{$this->id}";

        $command = implode(' | ', [
            "mysqldump -h {$from['host']} -u {$from['username']} -p{$from['password']} --insert-ignore --skip-add-drop-table {$from['database']} $table",
            "mysql -h {$to['host']} -u {$to['username']} -p{$to['password']} {$to['database']}"
        ]);

        $process = new Process($command);
        $process->setTimeout(0);
        $process->run();
        $process->wait();

        if ( ! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if (Schema::connection($connection)->hasTable($table)) {
            DB::connection($connection)->table($table)->truncate();
            Schema::connection($connection)->dropIfExists($table);
        }

        $this->database_id = $database_id;
        $this->save();
    }

    public function getLastConnectionAttribute()
    {
        $timestamp = $this->lastConnectTimestamp;

        if ( ! $timestamp)
            return null;

        return Carbon::createFromTimestamp($timestamp);
    }

    public function getLastConnectTimestampAttribute() {
        return max(strtotime($this->server_time), strtotime($this->ack_time));
    }
}
