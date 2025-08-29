<?php

namespace Tobuli\Helpers\GeoLocation\GeoServices;


use Tobuli\Helpers\GeoLocation\GeoSettings;
use Tobuli\Helpers\GeoLocation\Location;


class GeoGoogle extends AbstractGeoService
{
    private $curl;
    private $url;
    private $requestOptions = [];


    public function __construct(GeoSettings $settings)
    {
        parent::__construct($settings);

        $curl = new \Curl;
        $curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $curl->options['CURLOPT_TIMEOUT'] = 5;

        $this->curl = $curl;
        $this->url = 'https://maps.googleapis.com/maps/api/';
        $this->requestOptions = [
            'language' => config('tobuli.languages.' . config('app.locale') . '.iso', 'en'),
            'key'      => $this->settings->getApiKey(),
        ];
    }


    public function byAddress($address)
    {
        $address = $this->request('geocode', ['address' => $address]);

        return $address ? $this->locationObject($address) : null;
    }


    public function listByAddress($address)
    {
        if ( ! $addresses = $this->request('place/autocomplete', ['input' => $address])) {
            return [];
        }

        $locations = [];

        foreach ($addresses as $address) {
            $locations[] = $this->locationObject($address);
        }

        return $locations;
    }


    public function byCoordinates($lat, $lng)
    {
        $address = $this->request('geocode', ['latlng' => $lat . ',' . $lng]);

        return $address ? $this->locationObject($address) : null;
    }


    private function request($method, $options)
    {
        $response = $this->curl->get(
            $this->url . $method . '/json',
            array_merge($options, $this->requestOptions)
        );

        $response_body = json_decode($response->body, true);

        if ($response->headers['Status-Code'] != 200 || array_key_exists('error_message', $response_body)) {
            throw new \Exception(array_get($response_body, 'error_message') ?: 'Geocoder API error.');
        }

        if ($response_body['status'] == 'ZERO_RESULTS') {
            return null;
        }

        switch ($method) {
            case 'place/details':
                return $response_body['result'];
            case 'geocode':
                return $response_body['results'][0];
            default:
                return $response_body['predictions'];
        }
    }


    private function locationObject($address)
    {
        $components = [];

        $details = isset($address['address_components'])
            ? $address
            : $this->getPlaceDetails($address);

        if (array_get($details, 'address_components')) {
            foreach ($details['address_components'] as $component) {
                $components[$component['types'][0]] = $component['long_name'];
                $components[$component['types'][0] . '_short'] = $component['short_name'];
            }
        }

        return new Location([
            'place_id'      => array_get($address, 'place_id'),
            'lat'           => array_get($details, 'geometry.location.lat'),
            'lng'           => array_get($details, 'geometry.location.lng'),
            'address'       => array_get($details, 'formatted_address', array_get($address, 'description')),
            'country'       => array_get($components, 'country'),
            'country_code'  => array_get($components, 'country_short'),
            'state'         => array_get($components, 'administrative_area_level_1'),
            'county'        => array_get($components, 'administrative_area_level_2'),
            'city'          => array_get($components, 'locality'),
            'road'          => array_get($components, 'route'),
            'house'         => array_get($components, 'street_number'),
            'zip'           => array_get($components, 'postal_code'),
            'type'          => array_get($address['types'], 0),
        ]);
    }

    private function getPlaceDetails($address)
    {
        if (! isset($address['place_id'])) {
            return null;
        }

        return $this->request('place/details', ['place_id' => $address['place_id']]);
    }
}
