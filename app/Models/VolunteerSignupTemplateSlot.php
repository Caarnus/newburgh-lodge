<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerSignupTemplateSlot extends Model
{
    protected $fillable = [
        'volunteer_signup_template_role_id',
        'starts_at',
        'ends_at',
        'needed_count',
        'sort_order',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupTemplateRole::class, 'volunteer_signup_template_role_id');
    }
}
