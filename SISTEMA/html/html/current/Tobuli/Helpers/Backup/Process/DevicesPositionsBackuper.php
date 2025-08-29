<?php

namespace Tobuli\Helpers\Backup\Process;

use Illuminate\Database\QueryException;
use Tobuli\Entities\BackupProcess;
use Tobuli\Entities\TraccarDevice;
use Tobuli\Services\DatabaseService;

class DevicesPositionsBackuper extends AbstractBackuper
{
    private string $cmdBase;

    /**
     * @param TraccarDevice $item
     */
    protected function backup($item): bool
    {
        $positions = $item->positions();

        try {
            $count = $positions->count();
        } catch (QueryException $e) {
            if ($e->getCode() === '42S02') { // table or view not found
                return false;
            }

            throw $e;
        }

        if ($count === 0) {
            return false;
        }

        $cmd = "$this->cmdBase " . $positions->getRelated()->getTable();

        $this->uploader->process($cmd, $this->process, $item);

        return true;
    }

    protected function getProcessedItemId($item)
    {
        return $item->id;
    }

    protected function getItems(): iterable
    {
        $this->setCmdBase();

        return $this->getBaseQuery($this->process->source)
            ->where('id', '>', $this->process->last_item_id)
            ->orderBy('id', 'ASC')
            ->get();
    }

    private function setCmdBase(): void
    {
        $databaseId = $this->process->source;

        $databaseService = new DatabaseService();

        $config = $databaseService->getDatabaseConfig($databaseId);

        if ($config === null) {
            throw new \InvalidArgumentException("Database with ID `$databaseId`");
        }

        $this->cmdBase = "mysqldump"
            . " --insert-ignore"
            . " --no-create-info"
            . " --skip-add-drop-table"
            . " -u {$config['username']}"
            . " -h {$config['host']}"
            . " -p{$config['password']}"
            . " {$config['database']}";
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    private static function getBaseQuery(?string $databaseId)
    {
        return $databaseId
            ? TraccarDevice::where('database_id', $databaseId)
            : TraccarDevice::whereNull('database_id');
    }

    public static function makeProcess(string $source, array $options = []): BackupProcess
    {
        return new BackupProcess([
            'type'              => static::class,
            'source'            => $source,
            'options'           => $options,
            'duration_active'   => 30 * 60,
            'last_item_id'      => 0,
            'total'             => self::getBaseQuery($source)->count(),
        ]);
    }
}