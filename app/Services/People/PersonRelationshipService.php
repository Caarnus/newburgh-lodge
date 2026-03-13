<?php

namespace App\Services\People;

use App\Enums\RelationshipType;
use App\Models\PersonRelationship;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class PersonRelationshipService
{
    /**
     * @throws Throwable
     */
    public function createBidirectional(
        int $personId,
        int $relatedPersonId,
        RelationshipType $relationshipType,
        ?RelationshipType $inverseRelationshipType = null,
        mixed $anniversaryDate = null,
        bool $isPrimary = false,
        ?string $notes = null,
    ): array {
        if ($personId === $relatedPersonId) {
            throw new InvalidArgumentException('A person cannot be related to themselves.');
        }

        return DB::transaction(function () use (
            $personId,
            $relatedPersonId,
            $relationshipType,
            $inverseRelationshipType,
            $anniversaryDate,
            $isPrimary,
            $notes,
        ) {
            $resolvedAnniversaryDate = $this->anniversaryDateForPair(
                relationshipType: $relationshipType,
                inverseRelationshipType: $inverseRelationshipType,
                anniversaryDate: $anniversaryDate,
            );

            $forward = PersonRelationship::updateOrCreate(
                [
                    'person_id' => $personId,
                    'related_person_id' => $relatedPersonId,
                    'relationship_type' => $relationshipType,
                ],
                [
                    'inverse_relationship_type' => $inverseRelationshipType,
                    'anniversary_date' => $resolvedAnniversaryDate,
                    'is_primary' => $isPrimary,
                    'notes' => $notes,
                ]
            );

            $reverse = null;

            if ($inverseRelationshipType !== null) {
                $reverse = PersonRelationship::updateOrCreate(
                    [
                        'person_id' => $relatedPersonId,
                        'related_person_id' => $personId,
                        'relationship_type' => $inverseRelationshipType,
                    ],
                    [
                        'inverse_relationship_type' => $relationshipType,
                        'anniversary_date' => $resolvedAnniversaryDate,
                        'is_primary' => $isPrimary,
                        'notes' => $notes,
                    ]
                );
            }

            return [$forward, $reverse];
        });
    }

    /**
     * @throws Throwable
     */
    public function deleteBidirectional(PersonRelationship $relationship): void
    {
        DB::transaction(function () use ($relationship) {
            PersonRelationship::query()
                ->where('person_id', $relationship->related_person_id)
                ->where('related_person_id', $relationship->person_id)
                ->where('relationship_type', $relationship->inverse_relationship_type)
                ->delete();

            $relationship->delete();
        });
    }

    /**
     * @throws Throwable
     */
    public function updateBidirectional(
        PersonRelationship $relationship,
        RelationshipType $relationshipType,
        ?RelationshipType $inverseRelationshipType = null,
        mixed $anniversaryDate = null,
        bool $isPrimary = false,
        ?string $notes = null,
    ): array {
        if ($relationship->person_id === $relationship->related_person_id) {
            throw new InvalidArgumentException('A person cannot be related to themselves.');
        }

        return DB::transaction(function () use (
            $relationship,
            $relationshipType,
            $inverseRelationshipType,
            $anniversaryDate,
            $isPrimary,
            $notes,
        ) {
            if ($relationship->inverse_relationship_type !== null) {
                PersonRelationship::query()
                    ->where('person_id', $relationship->related_person_id)
                    ->where('related_person_id', $relationship->person_id)
                    ->where('relationship_type', $relationship->inverse_relationship_type)
                    ->delete();
            }

            $resolvedAnniversaryDate = $this->anniversaryDateForPair(
                relationshipType: $relationshipType,
                inverseRelationshipType: $inverseRelationshipType,
                anniversaryDate: $anniversaryDate,
            );

            $relationship->fill([
                'relationship_type' => $relationshipType,
                'inverse_relationship_type' => $inverseRelationshipType,
                'anniversary_date' => $resolvedAnniversaryDate,
                'is_primary' => $isPrimary,
                'notes' => $notes,
            ]);
            $relationship->save();

            $reverse = null;

            if ($inverseRelationshipType !== null) {
                $reverse = PersonRelationship::updateOrCreate(
                    [
                        'person_id' => $relationship->related_person_id,
                        'related_person_id' => $relationship->person_id,
                        'relationship_type' => $inverseRelationshipType,
                    ],
                    [
                        'inverse_relationship_type' => $relationshipType,
                        'anniversary_date' => $resolvedAnniversaryDate,
                        'is_primary' => $isPrimary,
                        'notes' => $notes,
                    ]
                );
            }

            return [$relationship->fresh(), $reverse];
        });
    }

    protected function anniversaryDateForPair(
        RelationshipType $relationshipType,
        ?RelationshipType $inverseRelationshipType,
        mixed $anniversaryDate,
    ): mixed {
        $isSpouseRelationship = $relationshipType === RelationshipType::Spouse
            || $inverseRelationshipType === RelationshipType::Spouse;

        return $isSpouseRelationship ? $anniversaryDate : null;
    }
}

