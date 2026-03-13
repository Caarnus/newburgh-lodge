<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\Audit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Officers\UpdateOfficerAssignmentsRequest;
use App\Models\LodgeOfficer;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OfficerAssignmentController extends Controller
{
    public function edit(): Response
    {
        $officers = LodgeOfficer::query()
            ->with('person.memberProfile')
            ->orderBy('display_order')
            ->get();

        $memberOptions = Person::query()
            ->select('people.*')
            ->join('member_profiles', 'member_profiles.person_id', '=', 'people.id')
            ->with('memberProfile')
            ->orderBy('people.last_name')
            ->orderBy('people.first_name')
            ->get()
            ->map(fn (Person $person) => [
                'id' => $person->id,
                'display_name' => $person->display_name,
                'member_number' => $person->memberProfile?->member_number,
            ])
            ->values()
            ->all();

        return Inertia::render('Admin/MemberDirectory/Officers', [
            'officers' => $officers
                ->map(fn (LodgeOfficer $officer) => [
                    'id' => $officer->id,
                    'slot_key' => $officer->slot_key,
                    'title' => $officer->title,
                    'person_id' => $officer->person_id,
                    'person' => $officer->person ? [
                        'id' => $officer->person->id,
                        'display_name' => $officer->person->display_name,
                        'member_number' => $officer->person->memberProfile?->member_number,
                    ] : null,
                ])
                ->values()
                ->all(),
            'memberOptions' => $memberOptions,
        ]);
    }

    public function update(UpdateOfficerAssignmentsRequest $request): RedirectResponse
    {
        $assignments = collect($request->validated('assignments'))
            ->mapWithKeys(fn (array $assignment) => [(int) $assignment['id'] => $assignment['person_id'] ?? null]);

        $officers = LodgeOfficer::query()
            ->with('person.memberProfile')
            ->whereIn('id', $assignments->keys())
            ->orderBy('display_order')
            ->get();

        $before = $this->snapshot($officers);

        DB::transaction(function () use ($officers, $assignments) {
            foreach ($officers as $officer) {
                $officer->update([
                    'person_id' => $assignments[(int) $officer->id] ?? null,
                ]);
            }
        });

        $after = $this->snapshot(
            LodgeOfficer::query()
                ->with('person.memberProfile')
                ->whereIn('id', $assignments->keys())
                ->orderBy('display_order')
                ->get()
        );

        if ($before !== $after) {
            Audit::log(
                $request,
                'lodge_officers.updated',
                changes: [
                    'before' => $before,
                    'after' => $after,
                ],
            );
        }

        return back()->with('success', 'Officer assignments updated.');
    }

    protected function snapshot(iterable $officers): array
    {
        return collect($officers)
            ->map(fn (LodgeOfficer $officer) => [
                'id' => $officer->id,
                'slot_key' => $officer->slot_key,
                'title' => $officer->title,
                'person_id' => $officer->person_id,
                'person_name' => $officer->person?->display_name,
                'member_number' => $officer->person?->memberProfile?->member_number,
            ])
            ->values()
            ->all();
    }
}
