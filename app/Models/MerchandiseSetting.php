<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchandiseSetting extends Model
{
    protected $fillable = [
        'order_notification_name',
        'order_notification_email',
    ];
}

