<?php namespace Tobuli\Validation;

class DeviceIconUploadValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'type' => 'required|in:icon,rotating',
            'file' => 'required|mimes:jpeg,gif,png|max:20000'
        ],
        'update' => [
            'type' => 'required|in:icon,rotating',
            'file' => 'mimes:jpeg,gif,png|max:20000'
        ]
    ];

}   //end of class


//EOF