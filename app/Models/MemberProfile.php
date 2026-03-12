<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'person_id',
        'member_number',
        'status',
        'ea_date',
        'fc_date',
        'mm_date',
        'demit_date',
        'roster_import_source',
        'last_imported_at',
        'can_auto_match_registration',
        'directory_visible',
    ];

    protected $casts = [
        'ea_date' => 'date',
        'fc_date' => 'date',
        'mm_date' => 'date',
        'demit_date' => 'date',
        'last_imported_at' => 'datetime',
        'can_auto_match_registration' => 'boolean',
        'directory_visible' => 'boolean',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
