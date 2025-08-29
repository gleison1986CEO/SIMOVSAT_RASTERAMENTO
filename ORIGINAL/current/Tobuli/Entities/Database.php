<?php namespace Tobuli\Entities;

use Eloquent;
use Illuminate\Support\Facades\Cache;

class Database extends Eloquent {
	protected $table = 'databases';

    protected $fillable = [];

    public function devices()
    {
        return $this->hasManyThrough(Device::class, TraccarDevice::class);
    }

    public static function getDatabaseName($database_id)
    {
        return $database_id && self::getDatabase($database_id) ? "database{$database_id}" : 'traccar_mysql';
    }

    public static function getDatabase($database_id)
    {
        if (empty($database_id))
            return config("database.connections.traccar_mysql");

        self::loadDatabaseConfig();

        return config("database.connections.database{$database_id}");
    }

    public static function getActiveDatabaseId() {
        $actives = Cache::store('array')->remember('device.position.active_databases', 1, function() {
            return Database::where('active', 1)->get();
        });

        if ($actives->isEmpty())
            return null;

        return $actives->random()->first()->id;
    }

    public static function loadDatabaseConfig() {
        Cache::store('array')->remember('device.position.databases', 1, function() {
            $databases = Database::all();

            foreach ($databases as $database)
                config()->set("database.connections.database{$database->id}", $database->toArray());

            return $databases;
        });
    }
}
