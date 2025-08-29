<?php namespace Tobuli\Validation;

class UserDriverFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'name' => 'required',
            'email' => 'email',
            'devices' => 'array',
            'devices.*' => 'integer'
        ],
        'update' => [
            'name' => 'required',
            'email' => 'email',
            'devices' => 'array',
            'devices.*' => 'integer'
        ],
        'silentUpdate' => [],
    ];

}   //end of class


//EOF