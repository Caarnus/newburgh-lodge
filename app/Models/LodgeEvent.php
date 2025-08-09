<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LodgeEvent extends Model
{
    protected function casts(): array
    {
        return [
            'start' => 'timestamp',
            'end' => 'timestamp',
            'is_public' => 'boolean',
        ];
    }
}
