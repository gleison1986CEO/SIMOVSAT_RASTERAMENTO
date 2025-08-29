<?php namespace Tobuli\Validation;

class SendCommandGprsFormValidator extends Validator
{

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'device_id' => 'required',
            'type'      => 'required'
        ],
        'commands' => [
            'device_id' => 'required'
        ],
    ];

}