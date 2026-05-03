<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerSignupSheetRole extends Model
{
    protected $fillable = [
        'volunteer_signup_sheet_id',
        'title',
        'description',
        'sort_order',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupSheet::class, 'volunteer_signup_sheet_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(VolunteerSignupSheetSlot::class, 'volunteer_signup_sheet_role_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
