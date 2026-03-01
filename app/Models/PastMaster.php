<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PastMaster extends Model
{
    protected $casts = [
        'deceased' => 'boolean',
    ];
}
