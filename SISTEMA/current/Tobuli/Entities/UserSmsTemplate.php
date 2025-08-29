<?php namespace Tobuli\Entities;

use Eloquent;

class UserSmsTemplate extends Eloquent {
	protected $table = 'user_sms_templates';

    protected $fillable = array(
        'user_id',
        'title',
        'message'
    );

    public function user() {
        return $this->belongsTo('Tobuli\Entities\User', 'user_id', 'id');
    }
}
