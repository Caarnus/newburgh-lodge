<?php

namespace App\Http\Controllers\Manage;

use App\Enums\RelationshipType;
use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\ShowPersonDirectoryRequest;
use App\Http\Requests\People\StorePersonDirectoryRequest;
use App\Models\MemberProfile;
use App\Models\Person;
use App\Services\People\PersonRelationshipService;
use App\Services\People\Directory\PeopleDirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PersonDirectoryController extends Controller
{
    public function create(PeopleDirectoryService $directoryService): Response
    {
        return Inertia::render('Admin/MemberDirectory/Create', [
            'memberStatusOptions' => $directoryService->memberStatusOptions(),
            'memberTypeOptions' => $directoryService->memberTypeOptions(),
            'relationshipTypeOptions' => $directoryService->relationshipTypeOptions(),
        ]);
    }

    public function store(
        StorePersonDirectoryRequest $request,
        PersonRelationshipService $relationshipService,
    ): RedirectResponse {
        $recordType = $request->string('record_type')->toString();

        $person = DB::transaction(function () use ($request, $recordType, $relationshipService) {
            $person = Person::query()->create([
                'first_name' => $request->string('first_name')->toString(),
                'middle_name' => $request->string('middle_name')->toString() ?: null,
                'last_name' => $request->string('last_name')->toString(),
                'suffix' => $request->string('suffix')->toString() ?: null,
                'preferred_name' => $request->string('preferred_name')->toString() ?: null,
                'email' => $request->string('email')->toString()
                    ? Str::lower($request->string('email')->toString())
                    : null,
                'phone' => $request->string('phone')->toString() ?: null,
                'address_line_1' => $request->string('address_line_1')->toString() ?: null,
                'address_line_2' => $request->string('address_line_2')->toString() ?: null,
                'city' => $request->string('city')->toString() ?: null,
                'state' => $request->string('state')->toString() ?: null,
                'postal_code' => $request->string('postal_code')->toString() ?: null,
                'birth_date' => $request->date('birth_date'),
                'notes' => $request->string('notes')->toString() ?: null,
                'is_deceased' => $request->boolean('is_deceased'),
                'death_date' => $request->boolean('is_deceased') ? $request->date('death_date') : null,
            ]);

            if ($recordType === 'member') {
                MemberProfile::query()->create([
                    'person_id' => $person->id,
                    'member_number' => $request->string('member_number')->toString() ?: null,
                    'status' => $request->string('member_status')->toString() ?: null,
                    'member_type' => $request->string('member_type')->toString() ?: null,
                    'ea_date' => $request->date('ea_date'),
                    'fc_date' => $request->date('fc_date'),
                    'mm_date' => $request->date('mm_date'),
                    'demit_date' => $request->date('demit_date'),
                    'can_auto_match_registration' => $request->boolean('can_auto_match_registration', true),
                    'directory_visible' => $request->boolean('directory_visible', true),
                ]);
            }

            if ($recordType === 'relative') {
                $relationshipType = RelationshipType::from($request->string('relationship_type')->toString());
                $inverseRelationshipType = $request->string('inverse_relationship_type')->toString()
                    ? RelationshipType::from($request->string('inverse_relationship_type')->toString())
                    : null;

                $relationshipService->createBidirectional(
                    personId: $person->id,
                    relatedPersonId: $request->integer('related_person_id'),
                    relationshipType: $relationshipType,
                    inverseRelationshipType: $inverseRelationshipType,
                    isPrimary: $request->boolean('relationship_is_primary'),
                    notes: $request->string('relationship_notes')->toString() ?: null,
                );
            }

            return $person;
        });

        $from = match ($recordType) {
            'member' => 'members',
            'relative' => 'relatives',
            default => null,
        };

        return redirect()
            ->route('manage.member-directory.people.show', array_filter([
                'person' => $person->id,
                'from' => $from,
            ]))
            ->with('success', 'Person record created.');
    }

    public function show(ShowPersonDirectoryRequest $request, Person $person, PeopleDirectoryService $directoryService): Response
    {
        $person = $directoryService->findPersonForDirectory($person->id);

        return Inertia::render('Admin/MemberDirectory/Show', [
            'person' => DirectoryPersonPresenter::detail($person),
            'relationshipTypeOptions' => $directoryService->relationshipTypeOptions(),
            'fromSection' => $request->string('from')->toString() ?: null,
        ]);
    }
}
