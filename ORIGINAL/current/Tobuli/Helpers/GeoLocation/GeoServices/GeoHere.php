<?php

namespace Tobuli\Helpers\GeoLocation\GeoServices;


use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\GeoLocation\GeoSettings;
use Tobuli\Helpers\GeoLocation\Location;


class GeoHere extends AbstractGeoService
{
    private $curl;
    private $urls;
    private $requestOptions = [];


    public function __construct(GeoSettings $settings)
    {
        parent::__construct($settings);

        $curl = new \Curl;
        $curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $curl->options['CURLOPT_TIMEOUT'] = 5;

        $this->curl = $curl;

        $this->urls = [
            "reverse" => "https://reverse.geocoder.ls.hereapi.com/6.2/reversegeocode.json",
            "geocode" => "https://geocoder.ls.hereapi.com/6.2/geocode.json"
        ];

        $this->requestOptions = [
            'apiKey' => $this->settings->getApiKey(),
            'language' => config('tobuli.languages.' . config('app.locale') . '.iso', 'en'),
        ];
    }


    public function byAddress($address)
    {
        $address = $this->request(
            'geocode',
            [
                'searchtext' => $address,
                'maxresults' => 1,
            ]
        );

        return $address ? $this->locationObject($address) : null;
    }


    public function listByAddress($address)
    {
        $addresses = $this->request(
            'geocode',
            [
                'searchtext' => $address,
                'maxresults' => 10
            ]
        );

        $locations = [];

        if (empty($addresses)) {
            return $locations;
        }

        if (isset($addresses['Location'])) {
            return [$this->locationObject($addresses)];
        }

        foreach ($addresses as $address) {
            $locations[] = $this->locationObject($address);
        }

        return $locations;
    }


    public function byCoordinates($lat, $lng)
    {
        $address = $this->request(
            'reverse',
            [
                'prox' => $lat . ',' . $lng . ',250',
                'mode' => 'retrieveAddresses',
                'maxresults' => '1',
            ]
        );

        return $address ? $this->locationObject($address) : null;
    }

    private function request($method, $options)
    {
        $response = $this->curl->get(
            $this->urls[$method],
            array_merge($options, $this->requestOptions)
        );

        $response_body = json_decode($response->body, true);

        if ($response->headers['Status-Code'] != 200 || $response_body == null)
            throw new \Exception('Geocoder API error.');

        $views = $response_body["Response"]["View"];
        if (empty($views))
            return null;

        $results = $views[0]["Result"];
        if (empty($results))
            return null;

        if (count($results) == 1)
            return $results[0];

        return $results;
    }

    private function locationObject($address)
    {
        $countryName = array_get($address, 'Address.Country');

        foreach (array_get($address, 'Location.Address.AdditionalData') as $data) {
            if($data["key"] == "CountryName")
                $countryName = $data["value"];
        }

        return new Location([
            'place_id'      => array_get($address, 'Location.LocationId'),
            'lat'           => array_get($address, 'Location.DisplayPosition.Latitude'),
            'lng'           => array_get($address, 'Location.DisplayPosition.Longitude'),
            'address'       => array_get($address, 'Location.Address.Label'),
            'country'       => $countryName,
            'country_code'  => array_get($address, 'Location.Address.Country'),
            'state'         => array_get($address, 'Location.Address.State'),
            'county'        => array_get($address, 'Location.Address.County'),
            'city'          => array_get($address, 'Location.Address.City'),
            'road'          => array_get($address, 'Location.Address.Street'),
            'house'         => array_get($address, 'Location.Address.HouseNumber'),
            'zip'           => array_get($address, 'Location.Address.PostalCode'),
            'type'          => array_get($address, 'MatchType'),
        ]);
    }
}