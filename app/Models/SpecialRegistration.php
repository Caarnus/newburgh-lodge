<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialRegistration extends Model
{
    protected $fillable = [
        'form', 'registration_type', 'name', 'email', 'phone', 'team_name', 'team_members',
    ];

    protected $casts = [
        'team_members' => 'array',
    ];
}
