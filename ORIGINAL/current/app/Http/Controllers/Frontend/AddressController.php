<?php
/**
 * Created by PhpStorm.
 * User: antanas
 * Date: 18.3.12
 * Time: 17.44
 */

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Validator;
use Tobuli\Exceptions\ValidationException;

class AddressController extends Controller
{
    public function get() {
        $data = request()->all();
        $validator = Validator::make($data, [
            'lat' => 'required|lat',
            'lon' => 'required|lng',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        try {
            $location = \CustomFacades\GeoLocation::byCoordinates($data['lat'], $data['lon']);

            if ($location)
                return $location->address;

            return trans('front.nothing_found_request');
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    public function autocomplete()
    {
        try {
            $locations = \CustomFacades\GeoLocation::listByAddress(request()->input('q'));
        } catch (\Exception $e) {
            $locations = [];
        }

        return response()->json(
            array_map(function($location){ return $location->toArray();}, $locations)
        );
    }

    public function map()
    {
        $data = request()->all();
        $validator = Validator::make($data, [
            'lat' => 'lat',
            'lng' => 'lng',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $lat = $data['lat'];
        $lng = $data['lng'];

        $data['coords'] = $lat && $lng ? '['.$lat.', '.$lng.']' : null;

        return view('front::Addresses.index')->with($data);
    }

    public function reverse()
    {
        $result = ['status' => 1];

        $data = request()->all();
        $validator = Validator::make($data, [
            'lat' => 'lat',
            'lng' => 'lng',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        try {
            $location = \CustomFacades\GeoLocation::byCoordinates($data['lat'], $data['lng']);

            if ( ! $location)
                return ['status' => 0, 'error' => trans('front.nothing_found_request')];

            $result['data'] = $location->toArray();

        } catch(\Exception $e) {
            $result = [
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }

        return $result;
    }
}
