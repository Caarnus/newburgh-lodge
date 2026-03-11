<?php

namespace App\Models\Concerns;

use App\Models\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPersonLink
{
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function isLinkedToPerson(): bool
    {
        return $this->person_id !== null;
    }
}
