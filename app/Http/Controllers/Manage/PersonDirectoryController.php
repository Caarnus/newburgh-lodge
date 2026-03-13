<?php

namespace App\Http\Controllers\Manage;

use App\Enums\RelationshipType;
use App\Helpers\Audit;
use App\Helpers\People\DirectoryPersonPresenter;
use App\Helpers\People\PeoplePermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\ShowPersonDirectoryRequest;
use App\Http\Requests\People\StorePersonDirectoryRequest;
use App\Http\Requests\People\UpdatePersonDirectoryRequest;
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
                'display_name_override' => $request->string('display_name_override')->toString() ?: null,
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
                    'ea_date' => $request->date('ea_date'),
                    'fc_date' => $request->date('fc_date'),
                    'mm_date' => $request->date('mm_date'),
                    'demit_date' => $request->date('demit_date'),
                    'past_master' => $request->boolean('past_master'),
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
            'memberStatusOptions' => $directoryService->memberStatusOptions(),
            'relationshipTypeOptions' => $directoryService->relationshipTypeOptions(),
            'fromSection' => $request->string('from')->toString() ?: null,
        ]);
    }

    public function update(UpdatePersonDirectoryRequest $request, Person $person): RedirectResponse
    {
        $canManageRecords = $request->user()?->can(PeoplePermissions::UPDATE_MEMBER_RECORDS) ?? false;
        $person->loadMissing('memberProfile');
        $before = $this->auditSnapshot($person, $canManageRecords);

        DB::transaction(function () use ($request, $person, $canManageRecords) {
            $personData = [
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
            ];

            if ($canManageRecords) {
                $personData = array_merge($personData, [
                    'first_name' => $request->string('first_name')->toString(),
                    'middle_name' => $request->string('middle_name')->toString() ?: null,
                    'last_name' => $request->string('last_name')->toString(),
                    'suffix' => $request->string('suffix')->toString() ?: null,
                    'display_name_override' => $request->string('display_name_override')->toString() ?: null,
                    'birth_date' => $request->date('birth_date'),
                    'notes' => $request->string('notes')->toString() ?: null,
                    'is_deceased' => $request->boolean('is_deceased'),
                    'death_date' => $request->boolean('is_deceased')
                        ? $request->date('death_date')
                        : null,
                ]);
            }

            $person->fill($personData);
            $person->save();

            if (! $canManageRecords) {
                return;
            }

            $memberProfileData = [
                'member_number' => $request->input('member_profile.member_number'),
                'status' => $request->input('member_profile.status'),
                'ea_date' => $request->date('member_profile.ea_date'),
                'fc_date' => $request->date('member_profile.fc_date'),
                'mm_date' => $request->date('member_profile.mm_date'),
                'demit_date' => $request->date('member_profile.demit_date'),
                'past_master' => $request->boolean(
                    'member_profile.past_master',
                    $person->memberProfile?->past_master ?? false
                ),
                'can_auto_match_registration' => $request->boolean(
                    'member_profile.can_auto_match_registration',
                    $person->memberProfile?->can_auto_match_registration ?? true
                ),
                'directory_visible' => $request->boolean(
                    'member_profile.directory_visible',
                    $person->memberProfile?->directory_visible ?? true
                ),
            ];

            $memberProfileInput = $request->input('member_profile', []);
            $hasMeaningfulMemberInput = collect([
                'member_number',
                'status',
                'ea_date',
                'fc_date',
                'mm_date',
                'demit_date',
                'past_master',
            ])->contains(fn (string $key) => filled($memberProfileInput[$key] ?? null));

            $shouldSyncMemberProfile = $person->memberProfile !== null
                || $hasMeaningfulMemberInput;

            if (! $shouldSyncMemberProfile) {
                return;
            }

            MemberProfile::query()->updateOrCreate(
                ['person_id' => $person->id],
                $memberProfileData,
            );
        });

        $person->refresh()->load('memberProfile');
        $after = $this->auditSnapshot($person, $canManageRecords);

        if ($before !== $after) {
            Audit::log(
                $request,
                $canManageRecords ? 'person.record.updated' : 'person.self_profile.updated',
                $person,
                changes: [
                    'before' => $before,
                    'after' => $after,
                ],
                meta: [
                    'self_service' => ! $canManageRecords,
                ],
            );
        }

        return back()->with('success', $canManageRecords ? 'Person record updated.' : 'Profile updated.');
    }

    protected function auditSnapshot(Person $person, bool $canManageRecords): array
    {
        $snapshot = [
            'preferred_name' => $person->preferred_name,
            'display_name_override' => $person->display_name_override,
            'email' => $person->email,
            'phone' => $person->phone,
            'address_line_1' => $person->address_line_1,
            'address_line_2' => $person->address_line_2,
            'city' => $person->city,
            'state' => $person->state,
            'postal_code' => $person->postal_code,
        ];

        if (! $canManageRecords) {
            return $snapshot;
        }

        return array_merge($snapshot, [
            'first_name' => $person->first_name,
            'middle_name' => $person->middle_name,
            'last_name' => $person->last_name,
            'suffix' => $person->suffix,
            'birth_date' => optional($person->birth_date)->toDateString(),
            'notes' => $person->notes,
            'is_deceased' => (bool) $person->is_deceased,
            'death_date' => optional($person->death_date)->toDateString(),
            'member_profile' => $person->memberProfile ? [
                'member_number' => $person->memberProfile->member_number,
                'status' => $person->memberProfile->status,
                'ea_date' => optional($person->memberProfile->ea_date)->toDateString(),
                'fc_date' => optional($person->memberProfile->fc_date)->toDateString(),
                'mm_date' => optional($person->memberProfile->mm_date)->toDateString(),
                'demit_date' => optional($person->memberProfile->demit_date)->toDateString(),
                'past_master' => (bool) $person->memberProfile->past_master,
                'can_auto_match_registration' => (bool) $person->memberProfile->can_auto_match_registration,
                'directory_visible' => (bool) $person->memberProfile->directory_visible,
            ] : null,
        ]);
    }
}
