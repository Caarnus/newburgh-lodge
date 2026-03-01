<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarshipApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cycle_year',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'residency_duration',
        'is_warrick_resident',
        'current_school',
        'education_level',
        'current_year',
        'expected_graduation',
        'planned_program',
        'gpa',
        'gpa_scale',
        'siblings',
        'activities',
        'awards',
        'lodge_relationship',
        'lodge_relationship_detail',
        'reason',
        'attachments',
        'status',
        'submitted_at',
        'ip_address',
        'user_agent',
        'content_hash',
        'email_verification_token',
        'email_verification_sent_at',
        'email_verified_at',
    ];

    protected $casts = [
        'is_warrick_resident' => 'boolean',
        'expected_graduation' => 'date',
        'attachments' => 'array',
        'submitted_at' => 'datetime',
        'email_verification_sent_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function reviews(): ScholarshipApplication|HasMany
    {
        return $this->hasMany(ScholarshipApplicationReview::class, 'scholarship_application_id');
    }
}
