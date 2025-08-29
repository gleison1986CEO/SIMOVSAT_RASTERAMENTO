<?php namespace Tobuli\Validation;

use CustomFacades\Repositories\TrackerPortRepo;
use Illuminate\Validation\Factory as IlluminateValidator;
use Tobuli\Entities\UserGprsTemplate;

class UserGprsTemplateFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'title'   => 'required',
            'message' => 'required',
            'adapted'    => 'required',
            'devices' => 'array',
            'devices.*' => 'integer',
        ],
        'update' => [
            'title' => 'required',
            'message' => 'required',
            'adapted'    => 'required',
            'devices' => 'array',
            'devices.*' => 'integer',
        ]
    ];

    public function __construct( IlluminateValidator $validator ) {
        $this->_validator = $validator;

        $protocols = TrackerPortRepo::getProtocolList();

        $this->rules['create']['protocol'] = 'in:0,,' . implode(',', array_keys($protocols));
        $this->rules['update']['protocol'] = 'in:0,,' . implode(',', array_keys($protocols));

        $adapties = UserGprsTemplate::getAdapties();

        $this->rules['create']['adapted'] = 'required|in:' . implode(',', array_keys($adapties));
        $this->rules['update']['adapted'] = 'required|in:' . implode(',', array_keys($adapties));
    }

}   //end of class


//EOF