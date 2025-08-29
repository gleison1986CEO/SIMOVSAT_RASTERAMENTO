<?php

namespace Tobuli\Entities\File;

class TrackerLog extends FileEntity
{
    protected function getDirectory($entity)
    {
        $config = config('tracker');

        return str_finish(pathinfo($config['logger.file'], PATHINFO_DIRNAME ), '/');
    }
}
