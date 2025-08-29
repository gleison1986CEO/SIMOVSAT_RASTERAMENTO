<?php

namespace Tobuli\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Tobuli\Entities\Device;
use Tobuli\Entities\TrackerPort;

class TrackerConfig
{
    const PATH_CONFIG = '/opt/traccar/conf/traccar.xml';

    protected $attributes = [];

    public function __construct()
    {
        $this->loadXML();
    }

    public function generate()
    {
        $this->load();

        $this->save( $this->build() );
    }

    public function update()
    {
        $this
            ->loadDefaults()
            ->load();

        $this->save( $this->build() );
    }

    public function reset()
    {
        settings("tracker", []);

        $this->attributes = [];

        $this
            ->loadDefaults()
            ->load();

        $this->save( $this->build() );
    }

    public function set($key, $value)
    {
        $this->merge($key, $value);

        settings("tracker.$key", $value);
    }

    public function get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function toArray()
    {
        return $this->attributes;
    }

    protected function merge($data, $value = null)
    {
        if ( ! $data)
            return $this;

        if (is_string($data))
            $this->attributes[$data] = $value;
        else
            $this->attributes = array_merge($this->attributes, $data);

        return $this;
    }

    protected function load()
    {
        $this
            ->loadConfig()
            ->loadStorage()
            ->loadSettings()
            ->loadPorts()
            ->loadForwards();

        return $this;
    }

    protected function loadStorage()
    {
        $path = storage_path('tracker.json');

        if ( ! File::exists($path))
            return $this;

        return $this->merge(json_decode(File::get($path), true));
    }

    protected function loadConfig()
    {
        return $this->merge(config('tracker'));
    }

    protected function loadSettings()
    {
        $settings = settings('tracker');

        if (empty($settings))
            return $this;

        return $this->merge(array_dot($settings));
    }

    protected function loadPorts()
    {
        $ports = TrackerPort::active()->get();

        foreach ($ports as $port) {
            $this->merge("{$port->name}.port", $port->port);

            if (empty($extras = json_decode($port['extra'], true)))
                continue;

            foreach ($extras as $key => $value) {
                $this->merge("{$port->name}.$key", $value);
            }
        }

        return $this;
    }

    protected function loadForwards()
    {

        $forward = Device::whereNotNull('forward')
            ->get()
            ->filter(function($device) {
                if (empty($device->forward))
                    return false;

                if ( ! array_get($device->forward, 'active'))
                    return false;

                try {
                    list($ip, $port) = explode(':', array_get($device->forward, 'ip'));
                } catch (\Exception $e) {
                    return false;
                }

                return true;
            })
            ->map(function($device) {
                list($ip, $port) = explode(':', array_get($device->forward, 'ip'));

                return "{$device->imei} {$ip} {$port} {$device->forward['protocol']}";
            })
            ->implode("\n");

        if (empty($forward))
            return $this;

        $this->merge("forwarder.config", "\n$forward");
    }

    protected function loadDefaults()
    {
        if(strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === false)
            {
                $protocolo = 'http://';
            }
        else
            {
                $protocolo = 'https://';
        }
        $json = file_get_contents($protocolo.$_SERVER['HTTP_HOST'].'/update/config.json');

        $data = json_decode($json, true);

        if (empty($data))
            throw new \Exception('Default config empty');

        return $this->merge($data);
    }

    protected function loadXML()
    {
        if ( ! File::exists(self::PATH_CONFIG))
            return $this;

        try {
            $xml = simplexml_load_string(File::get(self::PATH_CONFIG));
        } catch (\Exception $e) {
            return $this;
        }

        foreach ($xml->children() as $node) {
            $key = array_get($node->attributes(), "key");

            if (empty($key))
                continue;

            $this->merge(strval($key), strval($node));
        }

        return $this;
    }

    /*
     * return \SimpleXMLElement
     */
    protected function build()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . "<!DOCTYPE properties SYSTEM 'http://java.sun.com/dtd/properties.dtd'>"
            . '<properties></properties>'
        );

        foreach ($this->attributes as $key => $value) {
            $xml
                ->addChild('entry', $this->normalize($value))
                ->addAttribute('key', $key);
        }

        return $xml;
    }

    protected function normalize($value) {
        return str_replace(['&amp;', '&'], ['&', '&amp;'], $value);
    }

    protected function save(\SimpleXMLElement $xml)
    {
        $dom = new \DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        File::put(self::PATH_CONFIG, $dom->saveXML());
    }
}