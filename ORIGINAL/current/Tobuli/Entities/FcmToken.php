<?php

namespace Tobuli\Entities;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FcmToken extends Eloquent
{
    protected $table = 'fcm_tokens';

    protected $fillable = ['token'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
