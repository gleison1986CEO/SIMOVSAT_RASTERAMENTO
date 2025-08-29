<?php namespace Tobuli\Validation;

class SharingFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'active' => 'required|boolean',
            'name' => 'required|string',
            'devices' => 'required|array',
            'enable_expiration_date' => 'required|boolean',
            'expiration_date' => 'required_if:enable_expiration_date,1|date',
        ],
        'update' => [
            'active' => 'required|boolean',
            'name' => 'required|string',
            'devices' => 'required|array',
            'enable_expiration_date' => 'required|boolean',
            'expiration_date' => 'required_if:enable_expiration_date,1|date',
        ],
        'send' => [
            'devices' => 'required|array',
            'expiration_date' => 'required_if:expiration_by,date|date',
            'duration' => 'required_if:expiration_by,duration|integer',
            'send_sms' => 'boolean',
            'sms' => 'required_if:send_sms,1',
            'send_email' => 'boolean',
            'email' => 'required_if:send_email,1',
        ],
    ];
}
