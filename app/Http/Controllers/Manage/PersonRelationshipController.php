<?php

namespace App\Http\Controllers\Manage;

use App\Enums\RelationshipType;
use App\Helpers\Audit;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonRelationshipRequest;
use App\Http\Requests\People\UpdatePersonRelationshipRequest;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Services\People\PersonRelationshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PersonRelationshipController extends Controller
{
    public function store(
        StorePersonRelationshipRequest $request,
        Person $person,
        PersonRelationshipService $relationshipService,
    ): RedirectResponse {
        $relatedPersonCreated = false;
        $relatedPerson = null;
        $relationship = null;

        DB::transaction(function () use ($request, $person, $relationshipService, &$relatedPersonCreated, &$relatedPerson, &$relationship) {
            $relatedPersonId = null;

            if ($request->string('related_person_mode')->toString() === 'new') {
                $relatedPerson = Person::query()->create([
                    'first_name' => $request->string('new_person_first_name')->toString(),
                    'middle_name' => $request->string('new_person_middle_name')->toString() ?: null,
                    'last_name' => $request->string('new_person_last_name')->toString(),
                    'suffix' => $request->string('new_person_suffix')->toString() ?: null,
                    'preferred_name' => $request->string('new_person_preferred_name')->toString() ?: null,
                    'email' => $request->string('new_person_email')->toString()
                        ? Str::lower($request->string('new_person_email')->toString())
                        : null,
                    'phone' => $request->string('new_person_phone')->toString() ?: null,
                    'notes' => $request->string('new_person_notes')->toString() ?: null,
                    'is_deceased' => $request->boolean('new_person_is_deceased'),
                    'death_date' => $request->boolean('new_person_is_deceased')
                        ? $request->date('new_person_death_date')
                        : null,
                ]);

                $relatedPersonId = $relatedPerson->id;
                $relatedPersonCreated = true;
            } else {
                $relatedPersonId = $request->integer('related_person_id');
                $relatedPerson = Person::query()->find($relatedPersonId);
            }

            $relationshipType = RelationshipType::from($request->string('relationship_type')->toString());
            $inverseRelationshipType = $request->string('inverse_relationship_type')->toString()
                ? RelationshipType::from($request->string('inverse_relationship_type')->toString())
                : null;

            [$relationship] = $relationshipService->createBidirectional(
                personId: $person->id,
                relatedPersonId: $relatedPersonId,
                relationshipType: $relationshipType,
                inverseRelationshipType: $inverseRelationshipType,
                isPrimary: $request->boolean('is_primary'),
                notes: $request->string('notes')->toString() ?: null,
            );
        });

        Audit::log(
            $request,
            'person_relationship.created',
            $person,
            changes: [
                'after' => [
                    'relationship_id' => $relationship?->id,
                    'related_person_id' => $relatedPerson?->id,
                    'relationship_type' => $relationship?->relationship_type?->value,
                    'inverse_relationship_type' => $relationship?->inverse_relationship_type?->value,
                    'is_primary' => (bool) $relationship?->is_primary,
                    'notes' => $relationship?->notes,
                ],
            ],
            meta: [
                'related_person_created' => $relatedPersonCreated,
                'from' => $request->string('from')->toString() ?: null,
            ],
            secondary: $relatedPerson,
        );

        return $this->redirectToShow(
            person: $person,
            from: $request->string('from')->toString() ?: null,
            success: $relatedPersonCreated
                ? 'Relationship added and related person created.'
                : 'Relationship added.',
        );
    }

    public function update(
        UpdatePersonRelationshipRequest $request,
        Person $person,
        PersonRelationship $relationship,
        PersonRelationshipService $relationshipService,
    ): RedirectResponse {
        abort_unless((int) $relationship->person_id === (int) $person->id, 404);

        $before = [
            'relationship_type' => $relationship->relationship_type?->value,
            'inverse_relationship_type' => $relationship->inverse_relationship_type?->value,
            'is_primary' => (bool) $relationship->is_primary,
            'notes' => $relationship->notes,
        ];

        $relationshipType = RelationshipType::from($request->string('relationship_type')->toString());
        $inverseRelationshipType = $request->string('inverse_relationship_type')->toString()
            ? RelationshipType::from($request->string('inverse_relationship_type')->toString())
            : null;

        [$updatedRelationship] = $relationshipService->updateBidirectional(
            relationship: $relationship,
            relationshipType: $relationshipType,
            inverseRelationshipType: $inverseRelationshipType,
            isPrimary: $request->boolean('is_primary'),
            notes: $request->string('notes')->toString() ?: null,
        );

        Audit::log(
            $request,
            'person_relationship.updated',
            $person,
            changes: [
                'before' => $before,
                'after' => [
                    'relationship_type' => $updatedRelationship?->relationship_type?->value,
                    'inverse_relationship_type' => $updatedRelationship?->inverse_relationship_type?->value,
                    'is_primary' => (bool) $updatedRelationship?->is_primary,
                    'notes' => $updatedRelationship?->notes,
                ],
            ],
            meta: [
                'relationship_id' => $updatedRelationship?->id ?? $relationship->id,
                'related_person_id' => $relationship->related_person_id,
                'from' => $request->string('from')->toString() ?: null,
            ],
            secondary: $relationship->relatedPerson,
        );

        return $this->redirectToShow(
            person: $person,
            from: $request->string('from')->toString() ?: null,
            success: 'Relationship updated.',
        );
    }

    public function destroy(Request $request, Person $person, PersonRelationship $relationship, PersonRelationshipService $relationshipService): RedirectResponse
    {
        abort_unless((int) $relationship->person_id === (int) $person->id, 404);

        $request->validate([
            'from' => ['nullable', 'in:all,members,widows,orphans,relatives,others'],
        ]);

        $before = [
            'relationship_id' => $relationship->id,
            'related_person_id' => $relationship->related_person_id,
            'relationship_type' => $relationship->relationship_type?->value,
            'inverse_relationship_type' => $relationship->inverse_relationship_type?->value,
            'is_primary' => (bool) $relationship->is_primary,
            'notes' => $relationship->notes,
        ];
        $relatedPerson = $relationship->relatedPerson;

        $relationshipService->deleteBidirectional($relationship);

        Audit::log(
            $request,
            'person_relationship.deleted',
            $person,
            changes: [
                'before' => $before,
                'after' => null,
            ],
            meta: [
                'from' => $request->string('from')->toString() ?: null,
            ],
            secondary: $relatedPerson,
        );

        return $this->redirectToShow(
            person: $person,
            from: $request->string('from')->toString() ?: null,
            success: 'Relationship removed.',
        );
    }

    protected function redirectToShow(Person $person, ?string $from, string $success): RedirectResponse
    {
        return redirect()
            ->route('manage.member-directory.people.show', array_filter([
                'person' => $person->id,
                'from' => $from,
            ]))
            ->with('success', $success);
    }
}
