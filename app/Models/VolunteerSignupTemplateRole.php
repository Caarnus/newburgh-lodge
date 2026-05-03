<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerSignupTemplateRole extends Model
{
    protected $fillable = [
        'volunteer_signup_template_id',
        'title',
        'description',
        'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupTemplate::class, 'volunteer_signup_template_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(VolunteerSignupTemplateSlot::class, 'volunteer_signup_template_role_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
