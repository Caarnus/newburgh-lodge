<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentTile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'page',
        'type',
        'slug',
        'title',
        'config',
        'col_start',
        'row_start',
        'col_span',
        'row_span',
        'sort',
        'enabled',
    ];

    protected $casts = [
        'config' => 'array',
        'enabled' => 'boolean',
    ];
}
