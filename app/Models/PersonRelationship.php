<?php

namespace App\Models;

use App\Enums\RelationshipType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonRelationship extends Model
{
    protected $fillable = [
        'person_id',
        'related_person_id',
        'relationship_type',
        'inverse_relationship_type',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'relationship_type' => RelationshipType::class,
        'inverse_relationship_type' => RelationshipType::class,
        'is_primary' => 'boolean',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function relatedPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'related_person_id');
    }
}
