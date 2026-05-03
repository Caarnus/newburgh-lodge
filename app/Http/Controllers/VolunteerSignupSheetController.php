<?php

namespace App\Http\Controllers;

use App\Models\OrgEvent;
use App\Models\VolunteerSignupAssignment;
use App\Models\VolunteerSignupSheet;
use App\Models\VolunteerSignupTemplate;
use App\Services\VolunteerSignupReminderService;
use App\Services\VolunteerSignupStructureService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class VolunteerSignupSheetController extends Controller
{
    use AuthorizesRequests;

    public function edit(OrgEvent $event): Response
    {
        $this->authorize('update', $event);

        $sheet = $event->volunteerSignupSheet;
        if ($sheet) {
            $sheet->load([
                'roles.slots',
                'registrants.person',
                'registrants.assignments.slot.role',
            ]);
        }

        $templates = VolunteerSignupTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return Inertia::render('OrgEvents/Volunteers', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'start' => optional($event->start)?->toIso8601String(),
                'end' => optional($event->end)?->toIso8601String(),
                'location' => $event->location,
                'timezone' => $event->timezone ?: config('app.timezone', 'UTC'),
            ],
            'sheet' => $sheet ? $this->toSheetArray($sheet) : null,
            'templates' => $templates,
            'reminderPageUrl' => route('events.edit', $event),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function upsert(
        Request $request,
        OrgEvent $event,
        VolunteerSignupStructureService $structureService,
        VolunteerSignupReminderService $reminderService
    ): RedirectResponse {
        $this->authorize('update', $event);

        $existingSheet = $event->volunteerSignupSheet;
        $data = $this->validateSheet($request, $existingSheet);

        $sheet = VolunteerSignupSheet::updateOrCreate(
            ['org_event_id' => $event->id],
            [
                'volunteer_signup_template_id' => $data['volunteer_signup_template_id'] ?? null,
                'is_enabled' => (bool) $data['is_enabled'],
                'slug' => $data['slug'] ?? $existingSheet?->slug ?? $this->defaultSlug($event),
                'title_override' => $data['title_override'] ?? null,
                'description' => $data['description'] ?? null,
                'opens_at' => $data['opens_at'] ?? null,
                'closes_at' => $data['closes_at'] ?? null,
                'remind_week_before' => (bool) ($data['remind_week_before'] ?? true),
                'remind_day_before' => (bool) ($data['remind_day_before'] ?? true),
            ]
        );

        $structureService->syncSheetRoles($sheet, $data['roles']);
        $reminderService->syncForSheet($sheet->fresh());

        return back()->with('success', 'Volunteer signup saved.');
    }

    /**
     * @throws Throwable
     */
    public function applyTemplate(
        Request $request,
        OrgEvent $event,
        VolunteerSignupStructureService $structureService,
        VolunteerSignupReminderService $reminderService
    ): RedirectResponse {
        $this->authorize('update', $event);

        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:volunteer_signup_templates,id'],
        ]);

        $template = VolunteerSignupTemplate::query()->findOrFail($data['template_id']);

        $sheet = VolunteerSignupSheet::firstOrCreate(
            ['org_event_id' => $event->id],
            [
                'is_enabled' => false,
                'slug' => $this->defaultSlug($event),
                'remind_week_before' => true,
                'remind_day_before' => true,
            ]
        );

        $sheet->volunteer_signup_template_id = $template->id;
        $sheet->save();

        $structureService->applyTemplateToSheet($template, $sheet);
        $reminderService->syncForSheet($sheet->fresh());

        return back()->with('success', 'Template applied to event volunteer sheet.');
    }

    /**
     * @throws Throwable
     */
    public function saveAsTemplate(
        Request $request,
        OrgEvent $event,
        VolunteerSignupStructureService $structureService
    ): RedirectResponse {
        $this->authorize('update', $event);

        $sheet = $event->volunteerSignupSheet;
        abort_unless($sheet, 404);

        $sheet->load('roles.slots');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $template = VolunteerSignupTemplate::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by_user_id' => $request->user()?->id,
        ]);

        $roles = $sheet->roles
            ->sortBy(fn ($role) => [$role->sort_order, $role->id])
            ->values()
            ->map(fn ($role) => [
                'title' => $role->title,
                'description' => $role->description,
                'sort_order' => $role->sort_order,
                'slots' => $role->slots
                    ->sortBy(fn ($slot) => [$slot->sort_order, $slot->id])
                    ->values()
                    ->map(fn ($slot) => [
                        'starts_at' => substr((string) $slot->starts_at, 0, 5),
                        'ends_at' => substr((string) $slot->ends_at, 0, 5),
                        'needed_count' => $slot->needed_count,
                        'sort_order' => $slot->sort_order,
                    ])
                    ->all(),
            ])
            ->all();

        $structureService->syncTemplateRoles($template, $roles);

        return back()->with('success', 'Volunteer template saved from this event.');
    }

    /**
     * @throws Throwable
     */
    public function cancelAssignment(
        OrgEvent $event,
        VolunteerSignupAssignment $assignment,
        VolunteerSignupReminderService $reminderService
    ): RedirectResponse {
        $this->authorize('update', $event);

        $assignment->load('registrant.sheet');
        abort_unless($assignment->registrant?->sheet?->org_event_id === $event->id, 404);

        $assignment->status = 'canceled';
        $assignment->canceled_at = now();
        $assignment->save();

        $reminderService->syncForRegistrant($assignment->registrant->fresh());

        return back()->with('success', 'Volunteer assignment canceled.');
    }

    private function validateSheet(Request $request, ?VolunteerSignupSheet $sheet): array
    {
        $sheetId = $sheet?->id;

        return $request->validate([
            'volunteer_signup_template_id' => ['nullable', 'integer', 'exists:volunteer_signup_templates,id'],
            'is_enabled' => ['required', 'boolean'],
            'slug' => [
                Rule::requiredIf(fn () => (bool) $request->boolean('is_enabled') || (bool) $sheetId),
                'nullable',
                'string',
                'min:3',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('volunteer_signup_sheets', 'slug')->ignore($sheetId),
            ],
            'title_override' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
            'remind_week_before' => ['sometimes', 'boolean'],
            'remind_day_before' => ['sometimes', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*.id' => ['nullable', 'integer'],
            'roles.*.title' => ['required', 'string', 'max:120'],
            'roles.*.description' => ['nullable', 'string'],
            'roles.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'roles.*.slots' => ['required', 'array', 'min:1'],
            'roles.*.slots.*.id' => ['nullable', 'integer'],
            'roles.*.slots.*.starts_at' => ['required', 'date_format:H:i'],
            'roles.*.slots.*.ends_at' => ['required', 'date_format:H:i'],
            'roles.*.slots.*.needed_count' => ['required', 'integer', 'min:1', 'max:500'],
            'roles.*.slots.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
    }

    private function defaultSlug(OrgEvent $event): string
    {
        $base = Str::slug($event->title ?: 'event') . '-volunteers';

        $slug = $base;
        $i = 2;
        while (VolunteerSignupSheet::query()->where('slug', $slug)->where('org_event_id', '<>', $event->id)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return Str::limit($slug, 120, '');
    }

    private function toSheetArray(VolunteerSignupSheet $sheet): array
    {
        $counts = DB::table('volunteer_signup_assignments as vsa')
            ->selectRaw('vsa.volunteer_signup_sheet_slot_id as slot_id, COUNT(*) as active_count')
            ->where('vsa.status', 'active')
            ->whereIn('vsa.volunteer_signup_sheet_slot_id', $sheet->roles->flatMap->slots->pluck('id')->all())
            ->groupBy('vsa.volunteer_signup_sheet_slot_id')
            ->pluck('active_count', 'slot_id');

        return [
            'id' => $sheet->id,
            'org_event_id' => $sheet->org_event_id,
            'volunteer_signup_template_id' => $sheet->volunteer_signup_template_id,
            'is_enabled' => (bool) $sheet->is_enabled,
            'slug' => $sheet->slug,
            'title_override' => $sheet->title_override,
            'description' => $sheet->description,
            'opens_at' => optional($sheet->opens_at)?->toIso8601String(),
            'closes_at' => optional($sheet->closes_at)?->toIso8601String(),
            'remind_week_before' => (bool) $sheet->remind_week_before,
            'remind_day_before' => (bool) $sheet->remind_day_before,
            'public_url' => ($sheet->is_enabled && $sheet->slug)
                ? route('public.volunteer-signups.show', $sheet->slug)
                : null,
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
                                    'sort_order' => (int) $slot->sort_order,
                                    'active_count' => $activeCount,
                                    'remaining_count' => max(0, ((int) $slot->needed_count) - $activeCount),
                                ];
                            })
                            ->all(),
                    ];
                })
                ->all(),
            'registrants' => $sheet->registrants
                ->map(function ($registrant) {
                    $activeAssignments = $registrant->assignments
                        ->where('status', 'active')
                        ->sortBy(fn ($assignment) => [
                            $assignment->slot?->role?->sort_order ?? 0,
                            $assignment->slot?->sort_order ?? 0,
                            $assignment->id,
                        ])
                        ->values();

                    return [
                        'id' => $registrant->id,
                        'name' => $registrant->name,
                        'email' => $registrant->email,
                        'user_id' => $registrant->user_id,
                        'person_id' => $registrant->person_id,
                        'person_display_name' => $registrant->person?->display_name,
                        'assignments' => $activeAssignments->map(fn ($assignment) => [
                            'id' => $assignment->id,
                            'role_title' => $assignment->slot?->role?->title,
                            'starts_at' => $assignment->slot ? substr((string) $assignment->slot->starts_at, 0, 5) : null,
                            'ends_at' => $assignment->slot ? substr((string) $assignment->slot->ends_at, 0, 5) : null,
                        ])->all(),
                    ];
                })
                ->filter(fn ($r) => count($r['assignments']) > 0)
                ->sortBy('email')
                ->values()
                ->all(),
        ];
    }
}
