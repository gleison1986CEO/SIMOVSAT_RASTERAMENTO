<?php

namespace Tobuli\Helpers\GeoLocation;

use Illuminate\Support\Facades\Cache;
use Tobuli\Helpers\GeoLocation\GeoServices\AbstractGeoService;

class GeoLocation
{
    /**
     * @var AbstractGeoService
     */
    protected $service = null;

    /**
     * @var GeoCache
     */
    protected $cache;

    protected $cacheMethods = ['byCoordinates'];

    protected $methodServiceMap = [
        'byCoordinates' => 'primary',
        'listByAddress' => 'address',
        'byAddress'     => 'address',
    ];

    public function __construct()
    {
        if (settings('main_settings.geocoder_cache_enabled')) {
            $this->cache = new GeoCache();
        }
    }

    public function __call($method, $parameters)
    {
        $parameters = call_user_func_array([$this, $method . 'Normalize'], $parameters);

        if (!($this->cache && in_array($method, $this->cacheMethods)))
            return call_user_func_array([$this->getService($method), $method], $parameters);

        return $this->cache->get($method, $parameters, function () use ($method, $parameters) {
            return call_user_func_array([$this->getService($method), $method], $parameters);
        });
    }

    protected function setService(string $geocoder)
    {
        if (empty($geocoder))
            throw new \Exception('Geocoder required');

        $settings = settings('main_settings.geocoders.' . $geocoder);

        $this->service = $this->loadGeoService($settings);
    }

    protected function getService($method)
    {
        if ($this->service)
            return $this->service;

        try {
            $this->setService($this->methodServiceMap[$method] ?? '');
        } catch (\Exception $e) {
            $this->setService('primary');
        }

        return $this->service;
    }

    protected function loadGeoService($settings)
    {
        $class = 'Tobuli\Helpers\GeoLocation\GeoServices\Geo' . ucfirst($settings['api'] ?? '');

        if (!class_exists($class, true)) {
            throw new \InvalidArgumentException('GeoService class not found!');
        }

        return new $class((new GeoSettings())
            ->setApiKey($settings['api_key'] ?? '')
            ->setApiUrl($settings['api_url'] ?? '')
            ->setAppId($settings['api_app_id'] ?? '')
            ->setAppSecret($settings['api_app_secret'] ?? '')
        );
    }

    protected function byAddressNormalize($address)
    {
        return [$address];
    }

    protected function listByAddressNormalize($address)
    {
        return [$address];
    }

    protected function byCoordinatesNormalize($lat, $lng)
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            throw new \Exception('Bad coordinates input!');
        }

        $parameters[0] = round($lat, 6);
        $parameters[1] = round($lng, 6);

        return $parameters;
    }

    public function flushCache()
    {
        return $this->cache ? $this->cache->flush() : null;
    }
}