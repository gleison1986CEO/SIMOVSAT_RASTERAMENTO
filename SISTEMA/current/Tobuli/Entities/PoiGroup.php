<?php namespace Tobuli\Entities;

use Eloquent;

class PoiGroup extends Eloquent {
	protected $table = 'poi_groups';

    protected $fillable = array('title', 'user_id', 'open');

    public $timestamps = false;

    public function user() {
        return $this->belongsTo('Tobuli\Entities\User');
    }

    public function pois() {
        return $this->hasMany('Tobuli\Entities\Poi', 'group_id');
    }
}
