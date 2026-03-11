<?php

namespace App\Models;

use App\Enums\UserPersonLinkAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPersonLinkAudit extends Model
{
    protected $fillable = [
        'user_id',
        'previous_person_id',
        'current_person_id',
        'action',
        'match_strategy',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'action' => UserPersonLinkAction::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function previousPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'previous_person_id');
    }

    public function currentPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'current_person_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
