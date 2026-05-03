<?php

namespace App\Http\Controllers;

use App\Models\VolunteerSignupAssignment;
use App\Models\VolunteerSignupRegistrant;
use App\Models\VolunteerSignupSheet;
use App\Models\VolunteerSignupSheetSlot;
use App\Services\OrgEventRecurrenceService;
use App\Services\VolunteerSignupIdentityService;
use App\Services\VolunteerSignupReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class VolunteerSignupPublicController extends Controller
{
    public function show(VolunteerSignupSheet $volunteerSignupSheet, OrgEventRecurrenceService $recurrence): Response
    {
        $sheet = $volunteerSignupSheet->load('event', 'roles.slots');
        $this->abortIfUnavailable($sheet);

        $slotIds = $sheet->roles->flatMap->slots->pluck('id')->all();
        $counts = DB::table('volunteer_signup_assignments')
            ->selectRaw('volunteer_signup_sheet_slot_id as slot_id, COUNT(*) as active_count')
            ->where('status', 'active')
            ->whereIn('volunteer_signup_sheet_slot_id', $slotIds)
            ->groupBy('volunteer_signup_sheet_slot_id')
            ->pluck('active_count', 'slot_id');

        $event = $sheet->event;
        $next = $event ? $recurrence->nextOccurrence($event) : null;

        return Inertia::render('Public/VolunteerSignup/Show', [
            'sheet' => [
                'id' => $sheet->id,
                'slug' => $sheet->slug,
                'title' => $sheet->title_override ?: ($event?->title ?? 'Volunteer Signup'),
                'description' => $sheet->description,
                'opens_at' => optional($sheet->opens_at)?->toIso8601String(),
                'closes_at' => optional($sheet->closes_at)?->toIso8601String(),
            ],
            'event' => [
                'title' => $event?->title,
                'starts_at' => $next ? $next['effective_start_utc']->toIso8601String() : optional($event?->start)?->toIso8601String(),
                'ends_at' => $next ? $next['effective_end_utc']?->toIso8601String() : optional($event?->end)?->toIso8601String(),
                'location' => $event?->location,
                'timezone' => $event?->timezone ?: config('app.timezone', 'UTC'),
            ],
            'roles' => $sheet->roles
                ->sortBy(fn ($role) => [$role->sort_order, $role->id])
                ->values()
                ->map(function ($role) use ($counts) {
                    return [
                        'id' => $role->id,
                        'title' => $role->title,
                        'description' => $role->description,
                        'sort_order' => $role->sort_order,
                        'slots' => $role->slots
                            ->sortBy(fn ($slot) => [$slot->sort_order, $slot->id])
                            ->values()
                            ->map(function ($slot) use ($counts) {
                                $activeCount = (int) ($counts[$slot->id] ?? 0);

                                return [
                                    'id' => $slot->id,
                                    'starts_at' => substr((string) $slot->starts_at, 0, 5),
                                    'ends_at' => substr((string) $slot->ends_at, 0, 5),
                                    'needed_count' => (int) $slot->needed_count,
                                    'active_count' => $activeCount,
                                    'remaining_count' => max(0, ((int) $slot->needed_count) - $activeCount),
                                ];
                            })
                            ->all(),
                    ];
                })
                ->all(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(
        Request $request,
        VolunteerSignupSheet $volunteerSignupSheet,
        VolunteerSignupIdentityService $identityService,
        VolunteerSignupReminderService $reminderService
    ): RedirectResponse {
        $sheet = $volunteerSignupSheet->load('event', 'roles.slots');
        $this->abortIfUnavailable($sheet);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'slot_ids' => ['required', 'array', 'min:1'],
            'slot_ids.*' => ['integer'],
        ]);

        $registrant = null;

        DB::transaction(function () use ($data, $sheet, $identityService, &$registrant) {
            $slotIds = collect($data['slot_ids'])->map(fn ($id) => (int) $id)->unique()->values();

            $slots = VolunteerSignupSheetSlot::query()
                ->with('role')
                ->whereIn('id', $slotIds->all())
                ->whereHas('role', fn ($query) => $query->where('volunteer_signup_sheet_id', $sheet->id))
                ->get();

            if ($slots->count() !== $slotIds->count()) {
                throw ValidationException::withMessages([
                    'slot_ids' => 'One or more selected slots are invalid.',
                ]);
            }

            [$user, $person] = $identityService->resolveUserAndPerson($data['name'], $data['email']);

            $registrant = VolunteerSignupRegistrant::firstOrCreate(
                [
                    'volunteer_signup_sheet_id' => $sheet->id,
                    'email' => $data['email'],
                ],
                [
                    'name' => $data['name'],
                    'user_id' => $user->id,
                    'person_id' => $person?->id,
                ]
            );

            $registrant->fill([
                'name' => $data['name'],
                'user_id' => $user->id,
                'person_id' => $person?->id,
            ])->save();

            $fullSlots = [];

            foreach ($slots as $slot) {
                $alreadyActive = VolunteerSignupAssignment::query()
                    ->where('volunteer_signup_registrant_id', $registrant->id)
                    ->where('volunteer_signup_sheet_slot_id', $slot->id)
                    ->where('status', 'active')
                    ->exists();

                if ($alreadyActive) {
                    continue;
                }

                $activeCount = VolunteerSignupAssignment::query()
                    ->where('volunteer_signup_sheet_slot_id', $slot->id)
                    ->where('status', 'active')
                    ->count();

                if ($activeCount >= $slot->needed_count) {
                    $fullSlots[] = "{$slot->role?->title} ({substr((string) $slot->starts_at, 0, 5)} - " .
                        substr((string) $slot->ends_at, 0, 5) . ')';
                    continue;
                }

                VolunteerSignupAssignment::query()->create([
                    'volunteer_signup_registrant_id' => $registrant->id,
                    'volunteer_signup_sheet_slot_id' => $slot->id,
                    'status' => 'active',
                ]);
            }

            if ($fullSlots !== []) {
                throw ValidationException::withMessages([
                    'slot_ids' => 'Some slots filled while you were submitting: ' . implode(', ', $fullSlots),
                ]);
            }
        });

        if ($registrant) {
            $reminderService->syncForRegistrant($registrant->fresh());
        }

        return back()->with('success', 'Volunteer signup saved.');
    }

    private function abortIfUnavailable(VolunteerSignupSheet $sheet): void
    {
        abort_unless($sheet->is_enabled, 404);

        if ($sheet->opens_at && now()->lt($sheet->opens_at)) {
            abort(404);
        }

        if ($sheet->closes_at && now()->gt($sheet->closes_at)) {
            abort(404);
        }
    }
}
