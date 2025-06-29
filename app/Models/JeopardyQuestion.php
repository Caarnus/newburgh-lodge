<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JeopardyQuestion extends Model
{
    use SoftDeletes;
    use HasTimestamps;
}
