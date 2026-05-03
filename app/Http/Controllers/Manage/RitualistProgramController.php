<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\Audit;
use App\Helpers\People\PeoplePermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ritualist\StoreRitualEnrollmentRequest;
use App\Http\Requests\Ritualist\SyncRitualCompletionRequest;
use App\Http\Requests\Ritualist\UpsertRitualProgramRequest;
use App\Models\Person;
use App\Models\RitualCompletionRecord;
use App\Models\RitualEnrollment;
use App\Models\RitualProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RitualistProgramController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($this->canManage($request), 403);

        return Inertia::render('Admin/MemberDirectory/RitualistProgram', $this->buildPageProps(canManage: true));
    }

    public function show(Request $request): Response
    {
        abort_unless($this->canView($request), 403);

        return Inertia::render(
            'Admin/MemberDirectory/RitualistProgram',
            $this->buildPageProps(canManage: $this->canManage($request))
        );
    }

    protected function buildPageProps(bool $canManage): array
    {
        $programs = RitualProgram::query()
            ->orderByRaw($this->degreeOrderSql())
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $programsById = $programs->keyBy('id');

        $enrollments = RitualEnrollment::query()
            ->with([
                'person.memberProfile',
                'completionRecords',
            ])
            ->join('people', 'people.id', '=', 'ritual_enrollments.person_id')
            ->orderBy('people.last_name')
            ->orderBy('people.first_name')
            ->select('ritual_enrollments.*')
            ->get();

        $memberOptions = [];
        if ($canManage) {
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
                    'is_deceased' => (bool) $person->is_deceased,
                    'is_enrolled' => $enrollments->contains(fn (RitualEnrollment $enrollment) => $enrollment->person_id === $person->id),
                ])
                ->values()
                ->all();
        }

        return [
            'canManage' => $canManage,
            'programs' => $programs
                ->map(fn (RitualProgram $program) => [
                    'id' => $program->id,
                    'name' => $program->name,
                    'points' => (int) $program->points,
                    'degree_group' => $program->degree_group,
                    'display_order' => (int) $program->display_order,
                ])
                ->values()
                ->all(),
            'degreeOptions' => [
                ['value' => 'entered_apprentice', 'label' => 'Entered Apprentice'],
                ['value' => 'fellow_craft', 'label' => 'Fellow Craft'],
                ['value' => 'master_mason', 'label' => 'Master Mason'],
                ['value' => 'optional', 'label' => 'Optional'],
            ],
            'achievementLevels' => [
                ['label' => 'Ritualist', 'points' => 300],
                ['label' => 'Senior Ritualist', 'points' => 700],
                ['label' => 'Master Ritualist', 'points' => 1400],
            ],
            'memberOptions' => $memberOptions,
            'enrollments' => $enrollments
                ->map(function (RitualEnrollment $enrollment) use ($programsById) {
                    $completedProgramIds = $enrollment->completionRecords
                        ->where('completed', true)
                        ->pluck('ritual_program_id')
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    $totalPoints = $completedProgramIds->sum(
                        fn (int $programId) => (int) ($programsById[$programId]->points ?? 0)
                    );

                    return [
                        'id' => $enrollment->id,
                        'person_id' => $enrollment->person_id,
                        'person' => [
                            'id' => $enrollment->person?->id,
                            'display_name' => $enrollment->person?->display_name,
                            'member_number' => $enrollment->person?->memberProfile?->member_number,
                            'is_deceased' => (bool) $enrollment->person?->is_deceased,
                        ],
                        'completed_program_ids' => $completedProgramIds->all(),
                        'completed_count' => $completedProgramIds->count(),
                        'total_points' => $totalPoints,
                        'level_label' => $this->achievementLabelForPoints($totalPoints),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function storeEnrollment(StoreRitualEnrollmentRequest $request): RedirectResponse
    {
        $personId = (int) $request->validated('person_id');

        $enrollment = RitualEnrollment::firstOrCreate([
            'person_id' => $personId,
        ]);

        Audit::log(
            $request,
            'ritualist.enrollment.created',
            subject: $enrollment,
            changes: [
                'after' => [
                    'id' => $enrollment->id,
                    'person_id' => $enrollment->person_id,
                ],
            ],
        );

        return back()->with('success', 'Member added to ritualist tracking.');
    }

    public function destroyEnrollment(Request $request, RitualEnrollment $ritualEnrollment): RedirectResponse
    {
        abort_unless($request->user()?->can(PeoplePermissions::MANAGE_RITUALIST_PROGRAM), 403);

        $before = [
            'id' => $ritualEnrollment->id,
            'person_id' => $ritualEnrollment->person_id,
        ];

        $ritualEnrollment->delete();

        Audit::log(
            $request,
            'ritualist.enrollment.deleted',
            changes: ['before' => $before],
        );

        return back()->with('success', 'Member removed from ritualist tracking.');
    }

    public function syncCompletions(
        SyncRitualCompletionRequest $request,
        RitualEnrollment $ritualEnrollment
    ): RedirectResponse {
        $programIds = collect($request->validated('program_ids') ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowedProgramIds = RitualProgram::query()
            ->whereIn('id', $programIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $now = now();
        foreach ($allowedProgramIds as $programId) {
            RitualCompletionRecord::query()->updateOrCreate(
                [
                    'ritual_enrollment_id' => $ritualEnrollment->id,
                    'ritual_program_id' => $programId,
                ],
                [
                    'completed' => true,
                    'completed_at' => $now,
                ],
            );
        }

        RitualCompletionRecord::query()
            ->where('ritual_enrollment_id', $ritualEnrollment->id)
            ->whereNotIn('ritual_program_id', $allowedProgramIds)
            ->update([
                'completed' => false,
                'completed_at' => null,
            ]);

        Audit::log(
            $request,
            'ritualist.completions.synced',
            subject: $ritualEnrollment,
            changes: [
                'after' => [
                    'ritual_enrollment_id' => $ritualEnrollment->id,
                    'completed_program_ids' => $allowedProgramIds,
                ],
            ],
        );

        return back()->with('success', 'Member proficiencies updated.');
    }

    public function storeProgram(UpsertRitualProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! array_key_exists('display_order', $data) || $data['display_order'] === null) {
            $data['display_order'] = (int) RitualProgram::query()
                ->where('degree_group', $data['degree_group'])
                ->max('display_order') + 1;
        }

        $program = RitualProgram::create($data);

        Audit::log(
            $request,
            'ritualist.program.created',
            subject: $program,
            changes: ['after' => $program->toArray()],
        );

        return back()->with('success', 'Ritual program added.');
    }

    public function updateProgram(
        UpsertRitualProgramRequest $request,
        RitualProgram $ritualProgram
    ): RedirectResponse {
        $before = $ritualProgram->toArray();
        $ritualProgram->update($request->validated());

        Audit::log(
            $request,
            'ritualist.program.updated',
            subject: $ritualProgram,
            changes: [
                'before' => $before,
                'after' => $ritualProgram->fresh()->toArray(),
            ],
        );

        return back()->with('success', 'Ritual program updated.');
    }

    public function destroyProgram(Request $request, RitualProgram $ritualProgram): RedirectResponse
    {
        abort_unless($request->user()?->can(PeoplePermissions::MANAGE_RITUALIST_PROGRAM), 403);

        $before = $ritualProgram->toArray();
        $ritualProgram->delete();

        Audit::log(
            $request,
            'ritualist.program.deleted',
            changes: ['before' => $before],
        );

        return back()->with('success', 'Ritual program removed.');
    }

    protected function degreeOrderSql(): string
    {
        return "CASE degree_group
                    WHEN 'entered_apprentice' THEN 1
                    WHEN 'fellow_craft' THEN 2
                    WHEN 'master_mason' THEN 3
                    WHEN 'optional' THEN 4
                    ELSE 5
                END";
    }

    protected function achievementLabelForPoints(int $points): string
    {
        if ($points >= 1400) {
            return 'Master Ritualist';
        }

        if ($points >= 700) {
            return 'Senior Ritualist';
        }

        if ($points >= 300) {
            return 'Ritualist';
        }

        return 'In Progress';
    }

    protected function canManage(Request $request): bool
    {
        return $request->user()?->can(PeoplePermissions::MANAGE_RITUALIST_PROGRAM) ?? false;
    }

    protected function canView(Request $request): bool
    {
        return ($request->user()?->can(PeoplePermissions::VIEW_RITUALIST_PROGRAM) ?? false)
            || $this->canManage($request);
    }
}
