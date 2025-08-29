<?php

namespace Tobuli\Entities\File;

use Crypt;
use Eloquent;
use Illuminate\Support\Facades\File;

abstract class FileEntity
{
    public static $entity;

    protected $attributes = [
        'path',
        'basename',
        'name',
        'size',
        'created_at'
    ];

    protected $attrValues = [];

    abstract protected function getDirectory($entity);

    public function __construct($file = null)
    {
        $this->fillAttributes($file);
    }

    public static function setEntity($entity)
    {
        static::$entity = $entity;

        return new static;
    }

    public function fillAttributes($file)
    {
        if (!$file) return $this;

        foreach ($this->attributes as $key => $attribute) {
            $method = camel_case('fill' . ucfirst($attribute));

            if (!method_exists($this, $method)) continue;

            $this->attrValues[$attribute] = call_user_func_array(['self', $method], [$file]);
        }

        return $this;
    }

    public function fillBasename($file)
    {
        return basename($file);
    }

    public function fillPath($file)
    {
        return $file;
    }

    public function fillName($file)
    {
        $dir  = $this->getDirectory(static::$entity);
        $file = str_replace($dir, '', $file);

        return urlencode(Crypt::encrypt($file));
    }

    public function fillSize($file)
    {
        $bytes = sprintf('%u', filesize($file));

        if ($bytes > 0)
        {
            $unit = intval(log($bytes, 1024));
            $units = array('B', 'KB', 'MB', 'GB');

            if (array_key_exists($unit, $units) === true)
            {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes;
    }

    public function fillCreatedAt($file)
    {
        return date('Y-m-d H:i:s', File::lastModified($file));
    }

    public function isImage()
    {
        try {
            return is_array(getimagesize($this->path));
        } catch (\Exception $e) {
            return false;
        }
    }

    public function imageQuality()
    {
        if (!$this->isImage())
            return '-';

        list($width, $height) = getimagesize($this->path);

        switch (true) {
            case $width >= 1280 && $height >= 720:
                $Q = 'High';
                break;
            case $width > 800 && $height > 600:
                $Q = 'Normal';
                break;
            default:
                $Q = 'Low';
        }

        return $Q;
    }

    public function delete()
    {
        try {
            return unlink($this->path);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __get($key)
    {
        return $this->attrValues[$key];
    }

    public function __call($method, $parameters)
    {
        if (in_array($method, ['setEntity', 'getDirectory'])) {
            return call_user_func_array([$this, $method], $parameters);
        }

        $query = $this->newFileQuery();

        return call_user_func_array([$query, $method], $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }

    private function newFileQuery()
    {
        return new FileQuery($this, self::$entity);
    }
}