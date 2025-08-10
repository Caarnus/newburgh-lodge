<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PastMaster extends Model
{
    protected function casts(): array
    {
        return [
            'deceased' => 'boolean',
        ];
    }
}
