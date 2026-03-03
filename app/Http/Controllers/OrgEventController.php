<?php

namespace App\Http\Controllers;

use App\Models\EventSignupPage;
use App\Models\OrgEvent;
use App\Models\OrgEventOccurrenceOverride;
use App\Models\OrgEventType;
use App\Models\User;
use App\Services\EventSignupReminderService;
use App\Services\OrgEventRecurrenceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use RRule\RRule;

class OrgEventController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $actor = $request->user();

        $windowStart = now()->startOfMonth()->subMonths(6)->utc();
        $windowEnd   = now()->startOfMonth()->addMonths(18)->endOfMonth()->utc();

        $svc = app(OrgEventRecurrenceService::class);

        $events = OrgEvent::query()
            ->with(['type:id,name,color,category', 'occurrenceOverrides'])
            ->orderBy('start')
            ->get()
            ->flatMap(function (OrgEvent $event) use ($actor, $windowStart, $windowEnd, $svc) {

                // server-side: do not leak drafts to anon users
                if (!$actor && !$event->is_public) return [];

                $base = $this->toDto($event, withType: true, actor: $actor);

                $occRows = $svc->occurrencesBetween(
                    $event,
                    CarbonImmutable::instance($windowStart),
                    CarbonImmutable::instance($windowEnd),
                );

                // If non-repeating, occurrencesBetween returns one row
                if (!$occRows) return [];

                $out = [];
                foreach ($occRows as $row) {
                    $occIdUtc = $row['occurrence_id_utc'];
                    $dto = $base;

                    if ($event->repeats) {
                        $dto['parent_id'] = $event->id;
                        $dto['occurrence_id'] = $event->id . ':' . $occIdUtc->format('Ymd\THis\Z');
                    } else {
                        $dto['parent_id'] = null;
                        $dto['occurrence_id'] = null;
                    }

                    $dto['start'] = $row['effective_start_utc']->toIso8601String();
                    $dto['end']   = $row['effective_end_utc']?->toIso8601String();

                    // do not send RRULE down for calendar rendering
                    $dto['repeats'] = false;
                    $dto['rrule'] = null;

                    $out[] = $dto;
                }

                return $out;
            })
            ->values();

        $types = OrgEventType::query()
            ->select('id','name','category','color')
            ->orderBy('name')
            ->get();

        return Inertia::render('OrgEvents/Index', [
            'events'       => $events,
            'types'        => $types,
            'currentMonth' => now()->startOfMonth()->toDateString(),
        ]);
    }

    public function show(OrgEvent $event)
    {
        $this->authorize('view', $event);

        return $event;
    }

    public function create(Request $request)
    {
        $this->authorize('create', OrgEvent::class);

        $preselectStart = $request->string('start')->toString() ?: null;

        return Inertia::render('OrgEvents/Upsert', [
            'event'         => null,
            'types'         => OrgEventType::select('id', 'name', 'color')->orderBy('name')->get(),
            'preselectStart'=> $preselectStart,
        ]);
    }

    public function edit(OrgEvent $event)
    {
        $this->authorize('update', $event);

        return Inertia::render('OrgEvents/Upsert', [
            'event' => $this->toDto($event),
            'types' => OrgEventType::select('id', 'name', 'color')->orderBy('name')->get(),
            'preselectStart' => null,

            // NEW
            'signupPage' => $event->signupPage ? [
                'id' => $event->signupPage->id,
                'is_enabled' => (bool) $event->signupPage->is_enabled,
                'slug' => $event->signupPage->slug,
                'title_override' => $event->signupPage->title_override,
                'description' => $event->signupPage->description,
                'capacity' => $event->signupPage->capacity,
                'opens_at' => optional($event->signupPage->opens_at)?->toIso8601String(),
                'closes_at' => optional($event->signupPage->closes_at)?->toIso8601String(),
                'confirmation_message' => $event->signupPage->confirmation_message,
                'cover_image_url' => $event->signupPage->cover_image_path
                    ? Storage::url($event->signupPage->cover_image_path)
                    : null,
            ] : null,

            'occurrences' => app(OrgEventRecurrenceService::class)->upcomingOccurrences($event, 20),
            'occurrenceOverrides' => $event->occurrenceOverrides->map(fn ($o) => [
                'occurrence_starts_at' => $o->occurrence_starts_at->utc()->toIso8601String(),
                'override_starts_at' => $o->override_starts_at->utc()->toIso8601String(),
                'override_ends_at' => optional($o->override_ends_at)?->utc()->toIso8601String(),
                'is_canceled' => (bool) $o->is_canceled,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', OrgEvent::class);

        $data = $this->validateEvent($request);
        $this->normalizeDatesFromLocal($data);

        $tz = $data['timezone'] ?? config('app.timezone', 'UTC');
        $repeatOptions = $data['repeat_options'] ?? null;

        $rrule = null;
        if (!empty($data['repeats']) && $repeatOptions && ($repeatOptions['mode'] ?? 'none') !== 'none') {
            $rrule = app(OrgEventRecurrenceService::class)
                ->buildRRuleFromOptions($repeatOptions, $data['_start_local_carbon'], $tz);
        }

        OrgEvent::create([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'location'         => $data['location'] ?? null,
            'timezone'         => $tz,
            'all_day'          => (bool)($data['all_day'] ?? false),
            'start'            => $data['start'],
            'end'              => $data['end'],
            'type_id'          => $data['type_id'] ?? null,

            'masons_only'      => (bool)($data['masons_only'] ?? false),
            'open_to'          => $data['open_to'] ?? 'all',
            'degree_required'  => $data['degree_required'] ?? 'none',
            'is_public'        => !array_key_exists('is_public', $data) || $data['is_public'],

            'repeats'          => (bool)($data['repeats'] ?? false),
            'rrule'            => (!empty($data['repeats']) ? $rrule : null),
        ]);

        return redirect()->route('events.index')->with('success', 'Event created.');
    }

    public function update(Request $request, OrgEvent $event)
    {
        $this->authorize('update', $event);

        $data = $this->validateEvent($request);
        $this->normalizeDatesFromLocal($data);

        $tz = $data['timezone'] ?? config('app.timezone', 'UTC');
        $repeatOptions = $data['repeat_options'] ?? null;

        $rrule = null;
        if (!empty($data['repeats']) && $repeatOptions && ($repeatOptions['mode'] ?? 'none') !== 'none') {
            $rrule = app(OrgEventRecurrenceService::class)
                ->buildRRuleFromOptions($repeatOptions, $data['_start_local_carbon'], $tz);
        }

        $event->update([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'location'         => $data['location'] ?? null,
            'timezone'         => $tz,
            'all_day'          => (bool)($data['all_day'] ?? false),
            'start'            => $data['start'],
            'end'              => $data['end'],
            'type_id'          => $data['type_id'] ?? null,

            'masons_only'      => (bool)($data['masons_only'] ?? false),
            'open_to'          => $data['open_to'] ?? 'all',
            'degree_required'  => $data['degree_required'] ?? 'none',
            'is_public'        => array_key_exists('is_public', $data) ? (bool)$data['is_public'] : $event->is_public,

            'repeats'          => (bool)($data['repeats'] ?? false),
            'rrule'            => (!empty($data['repeats']) ? $rrule : null),
        ]);

        app(EventSignupReminderService::class)->syncForEvent($event->fresh('signupPage'));

        return redirect()->route('events.index')->with('success', 'Event updated.');
    }

    public function destroy(OrgEvent $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event deleted.');
    }

    public function fetchEvents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'days_ahead' => ['nullable', 'integer', 'min:1', 'max:365'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:80'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $days = $data['days_ahead'] ?? 30;
        $limit = $data['limit'] ?? 5;

        $now = Carbon::now();
        $until = Carbon::now()->addDays($days);

        $q = OrgEvent::whereBetween('start', [$now, $until])
            ->orderBy('start','asc');

        if (!empty($data['categories'])) {
            $q->whereIn('categories', $data['categories']);
        }

        $rows = $q->limit($limit)->get()->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start,
                'end' => $event->end ?? null,
                'url' => 'https://www.google.com',
                'type' => $event->type->name,
            ];
        });

        return response()->json($rows);
    }

    public function upsertSignupPage(Request $request, OrgEvent $event)
    {
        $this->authorize('update', $event);

        $event->load('signupPage');
        $pageId = $event->signupPage?->id;

        $data = $request->validateWithBag('signupPage', [
            'is_enabled' => ['required', 'boolean'],

            // Only required when enabling OR when a page already exists
            'slug' => [
                Rule::requiredIf(fn () => (bool) $request->boolean('is_enabled') || (bool) $pageId),
                'nullable',
                'string',
                'min:3',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('event_signup_pages', 'slug')->ignore($pageId),
            ],

            'title_override' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],

            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
            'confirmation_message' => ['nullable', 'string', 'max:2000'],

            'cover_image' => ['nullable', 'image', 'max:5120'],
            'remove_cover_image' => ['sometimes', 'boolean'],
        ]);

        // If user disables AND no page exists: nothing to do.
        if (!$data['is_enabled'] && !$event->signupPage) {
            return back()->with('success', 'Signup page disabled.');
        }

        // If disabling existing page: keep record + slug, just turn it off (preserves signups/history)
        if (!$data['is_enabled'] && $event->signupPage) {
            $event->signupPage->update(['is_enabled' => false]);
            return back()->with('success', 'Signup page disabled.');
        }

        $page = EventSignupPage::updateOrCreate(
            ['org_event_id' => $event->id],
            [
                'is_enabled' => true,
                'slug' => $data['slug'],
                'title_override' => $data['title_override'] ?? null,
                'description' => $data['description'] ?? null,
                'capacity' => $data['capacity'] ?? null,
                'opens_at' => $data['opens_at'] ?? null,
                'closes_at' => $data['closes_at'] ?? null,
                'confirmation_message' => $data['confirmation_message'] ?? null,
            ]
        );

        if (!empty($data['remove_cover_image']) && $page->cover_image_path) {
            Storage::disk('public')->delete($page->cover_image_path);
            $page->cover_image_path = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($page->cover_image_path) {
                Storage::disk('public')->delete($page->cover_image_path);
            }

            $page->cover_image_path = $request->file('cover_image')->storePublicly(
                "event-signup-pages/{$event->id}",
                'public'
            );
        }

        $page->save();

        return back()->with('success', 'Signup page saved.');
    }

    public function destroySignupPage(Request $request, OrgEvent $event)
    {
        $this->authorize('update', $event);

        $event->load('signupPage');

        if ($event->signupPage) {
            if ($event->signupPage->cover_image_path) {
                Storage::disk('public')->delete($event->signupPage->cover_image_path);
            }
            $event->signupPage->delete(); // cascades signups/reminders (by FK)
        }

        return back()->with('success', 'Signup page deleted.');
    }

    public function upsertOccurrenceOverride(Request $request, OrgEvent $event)
    {
        $this->authorize('update', $event);

        $data = $request->validateWithBag('occurrenceOverride', [
            'occurrence_starts_at' => ['required', 'date'],
            'override_starts_at' => ['required', 'date'],
            'override_ends_at' => ['nullable', 'date', 'after:override_starts_at'],
            'is_canceled' => ['required', 'boolean'],
        ]);

        OrgEventOccurrenceOverride::updateOrCreate(
            [
                'org_event_id' => $event->id,
                'occurrence_starts_at' => Carbon::parse($data['occurrence_starts_at'])->utc(),
            ],
            [
                'override_starts_at' => Carbon::parse($data['override_starts_at'])->utc(),
                'override_ends_at' => $data['override_ends_at'] ? Carbon::parse($data['override_ends_at'])->utc() : null,
                'is_canceled' => (bool) $data['is_canceled'],
            ]
        );

        return back()->with('success', 'Occurrence override saved.');
    }

    public function destroyOccurrenceOverride(Request $request, OrgEvent $event)
    {
        $this->authorize('update', $event);

        $data = $request->validateWithBag('occurrenceOverride', [
            'occurrence_starts_at' => ['required', 'date'],
        ]);

        OrgEventOccurrenceOverride::where('org_event_id', $event->id)
            ->where('occurrence_starts_at', Carbon::parse($data['occurrence_starts_at'])->utc())
            ->delete();

        return back()->with('success', 'Occurrence override removed.');
    }

    //Helper functions for controller
    private function validateEvent(Request $request): array
    {
        $request->merge([
            'degree_required' => $request->filled('degree_required') ? strtolower($request->input('degree_required')) : 'none',
            'open_to'         => $request->filled('open_to') ? strtolower($request->input('open_to')) : 'all',
        ]);

        return $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'location'         => ['nullable', 'string', 'max:255'],
            'timezone'         => ['nullable','string', function($attr,$val,$fail){
                if ($val && !in_array($val, DateTimeZone::listIdentifiers())) $fail('Invalid timezone.');
            }],

            'all_day'          => ['sometimes', 'boolean'],

            // NEW: local wall-clock fields from Vue
            'start_local'      => ['required', 'date_format:Y-m-d\TH:i'],
            'end_local'        => ['nullable', 'date_format:Y-m-d\TH:i', 'after_or_equal:start_local'],

            'type_id'          => ['nullable', 'integer', 'exists:org_event_types,id'],

            'masons_only'      => ['sometimes', 'boolean'],
            'open_to'          => ['required', 'in:all,members,officers'],
            'degree_required'  => ['required', 'in:none,entered apprentice,fellowcraft,master mason'],
            'is_public'        => ['sometimes', 'boolean'],

            'repeats'          => ['sometimes', 'boolean'],

            // NEW: recurrence options (backend builds rrule)
            'repeat_options'               => ['nullable', 'array'],
            'repeat_options.mode'          => ['nullable', 'in:none,nth-weekday,interval'],
            'repeat_options.nth'           => ['nullable', 'integer'],
            'repeat_options.weekday'       => ['nullable', 'string'],
            'repeat_options.freq'          => ['nullable', 'string'],
            'repeat_options.interval'      => ['nullable', 'integer', 'min:1'],
            'repeat_options.byweekday'     => ['nullable', 'array'],
            'repeat_options.byweekday.*'   => ['string'],
            'repeat_options.ends'          => ['nullable', 'in:never,until,count'],
            'repeat_options.until'         => ['nullable', 'date_format:Y-m-d'],
            'repeat_options.count'         => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);
    }

    private function normalizeDatesFromLocal(array &$data): void
    {
        $tz = $data['timezone'] ?? config('app.timezone', 'UTC');
        $allDay = (bool)($data['all_day'] ?? false);

        $startLocal = Carbon::createFromFormat('Y-m-d\TH:i', $data['start_local'], $tz);
        if ($allDay) $startLocal = $startLocal->startOfDay();

        $endLocal = null;
        if (!empty($data['end_local'])) {
            $endLocal = Carbon::createFromFormat('Y-m-d\TH:i', $data['end_local'], $tz);
            if ($allDay) $endLocal = $endLocal->endOfDay();
        }

        $data['start'] = $startLocal->copy()->utc();
        $data['end']   = $endLocal ? $endLocal->copy()->utc() : null;

        // service expects CarbonImmutable in the event timezone
        $data['_start_local_carbon'] = CarbonImmutable::instance($startLocal);
    }

    private function toDto(OrgEvent $event, bool $withType = false, ?User $actor = null): array
    {
        $toIsoUtc = function ($v) {
            if (!$v) return null;
            $c = $v instanceof \DateTimeInterface ? Carbon::instance($v) : Carbon::parse($v);
            return $c->copy()->utc()->toIso8601String();
        };

        $tz = $event->timezone ?: config('app.timezone', 'UTC');

        $dto = [
            'id'              => $event->id,
            'title'           => $event->title,
            'description'     => $event->description,
            'location'        => $event->location,
            'timezone'        => $event->timezone,
            'all_day'         => (bool)$event->all_day,
            'start'           => $toIsoUtc($event->start),
            'end'             => $toIsoUtc($event->end),

            // NEW: what Upsert.vue expects
            'start_local'     => $event->start ? $event->start->copy()->timezone($tz)->format('Y-m-d\TH:i') : null,
            'end_local'       => $event->end ? $event->end->copy()->timezone($tz)->format('Y-m-d\TH:i') : null,

            'type_id'         => $event->type_id,

            'masons_only'     => (bool)$event->masons_only,
            'open_to'         => $event->open_to ?? 'all',
            'degree_required' => $event->degree_required ?? 'none',
            'is_public'       => (bool)($event->is_public ?? true),

            'repeats'         => (bool)$event->repeats,

            // optional to expose, but Upsert.vue uses repeat_options instead
            'rrule'           => $event->rrule,

            'repeat_options'  => $event->rrule
                ? app(OrgEventRecurrenceService::class)->parseRepeatOptions($event->rrule, $tz)
                : null,
        ];

        if ($withType && $event->relationLoaded('type')) {
            $dto['type'] = $event->type ? [
                'id'       => $event->type->id,
                'name'     => $event->type->name,
                'category' => $event->type->category,
                'color'    => $event->type->color,
            ] : null;
        }

        if ($actor) {
            $dto['can_edit'] = $actor->can('update', $event);
        }

        return $dto;
    }
}
