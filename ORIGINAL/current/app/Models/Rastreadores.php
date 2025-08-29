<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rastreadores extends Model
{
    protected $fillable = [
        'imei',
        'modelo',
        'equipamento'
    ];
}


//executar migração
//php artisan migrate