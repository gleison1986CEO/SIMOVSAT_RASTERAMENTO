<?php namespace Tobuli\Entities;

use Eloquent;

class UserGprsTemplate extends Eloquent {
	protected $table = 'user_gprs_templates';

    protected $fillable = array(
        'user_id',
        'title',
        'message',
        'protocol',
        'adapted'
    );

    public function user() {
        return $this->belongsTo('Tobuli\Entities\User', 'user_id', 'id');
    }

    public function devices() {
        return $this->belongsToMany('Tobuli\Entities\Device', 'user_gprs_template_devices', 'user_gprs_template_id', 'device_id');
    }

    public static function getAdapties()
    {
        return [
            '0'        => trans('front.all'),
            'protocol' => trans('front.protocol'),
            'devices'  => trans('front.devices'),
        ];
    }

    public function getAdaptedTitleAttribute($value)
    {
        return self::getAdapties()[$this->adapted] ?? trans('front.all');
    }

    public function setProtocolAttribute($value)
    {
        $this->attributes['protocol'] = empty($value) ? null : $value;
    }

    public function setAdaptedAttribute($value)
    {
        $this->attributes['adapted'] = empty($value) ? null : $value;
    }

    public function isAdaptedFromDevice($device)
    {
        if (empty($this->adapted))
            return true;

        if ($this->adapted == 'protocol' && $this->protocol == $device->protocol)
            return true;

        if ($this->adapted == 'devices' && $this->devices->contains('id', $device->id))
            return true;

        return false;
    }
}
