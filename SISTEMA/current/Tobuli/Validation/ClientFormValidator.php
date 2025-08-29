<?php namespace Tobuli\Validation;

class ClientFormValidator extends Validator {

    /**
     * @var array Validation rules for the test form, they can contain in-built Laravel rules or our custom rules
     */
    public $rules = [
        'create' => [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
            'available_maps' => 'required|array',
            'group_id' => 'required|in:1,2,3,4,5',
            'devices_limit' => 'required_if:enable_devices_limit,1|integer',
            'expiration_date' => 'required_if:enable_expiration_date,1|date',
        ],
        'update' => [
            'email' => 'required|email|unique:users,email,%s',
            'password' => 'confirmed',
            'available_maps' => 'required|array',
            'group_id' => 'required|in:1,2,3,4,5',
            'devices_limit' => 'required_if:enable_devices_limit,1|integer',
            'expiration_date' => 'required_if:enable_expiration_date,1|date',
        ]
    ];

    public function validate($name, array $data, $id = NULL)
    {
        $this->rules[$name]['available_maps.*'] = 'integer|in:' . implode(',', array_keys(getAvailableMaps()));

        parent::validate($name, $data, $id);
    }
}