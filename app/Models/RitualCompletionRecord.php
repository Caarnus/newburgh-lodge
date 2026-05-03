<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RitualCompletionRecord extends Model
{
    protected $table = 'ritual_records';

    protected $fillable = [
        'ritual_enrollment_id',
        'ritual_program_id',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(RitualEnrollment::class, 'ritual_enrollment_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(RitualProgram::class, 'ritual_program_id');
    }
}
