<?php

namespace App\Http\Controllers;

use App\Models\OrgEvent;
use App\Models\OrgEventType;
use App\Models\User;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrgEventController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $events = OrgEvent::query()
            ->with('type:id,name,color,category')
            ->orderBy('start')
            ->get()
            ->map(fn ($event) =>
                $this->toDto($event, withType: true, actor: $request->user())
            );

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
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', OrgEvent::class);

        $data = $this->validateEvent($request);

        $this->normalizeDates($data);
        $tz = $data['timezone'] ?? config('app.timezone', 'UTC');

        OrgEvent::create([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'location'         => $data['location'] ?? null,
            'timezone'         => $tz,
            'all_day'          => (bool)($data['all_day'] ?? false),
            'start'            => $data['start'] ?? null,
            'end'              => $data['end'] ?? null,
            'type_id'          => $data['type_id'] ?? null,

            'masons_only'      => (bool)($data['masons_only'] ?? false),
            'open_to'          => $data['open_to'] ?? 'all',
            'degree_required'  => $data['degree_required'] ?? 'none',
            'is_public'        => !array_key_exists('is_public', $data) || $data['is_public'],

            'repeats'          => (bool)($data['repeats'] ?? false),
            'rrule'            => $data['repeats'] ? ($data['rrule'] ?? null) : null,
        ]);

        return redirect()->route('events.index')->with('success', 'Event created.');
    }

    public function update(Request $request, OrgEvent $event)
    {
        $this->authorize('update', $event);

        $data = $this->validateEvent($request);

        $this->normalizeDates($data);
        $tz = $data['timezone'] ?? config('app.timezone', 'UTC');

        $event->update([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'location'         => $data['location'] ?? null,
            'timezone'         => $tz,
            'all_day'          => (bool)($data['all_day'] ?? false),
            'start'            => $data['start'] ?? null,
            'end'              => $data['end'] ?? null,
            'type_id'          => $data['type_id'] ?? null,

            'masons_only'      => (bool)($data['masons_only'] ?? false),
            'open_to'          => $data['open_to'] ?? 'all',
            'degree_required'  => $data['degree_required'] ?? 'none',
            'is_public'        => array_key_exists('is_public', $data) ? (bool)$data['is_public'] : $event->is_public,

            'repeats'          => (bool)($data['repeats'] ?? false),
            'rrule'            => $data['repeats'] ? ($data['rrule'] ?? null) : null,
        ]);

        return redirect()->route('events.index')->with('success', 'Event updated.');
    }

    public function destroy(OrgEvent $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event deleted.');
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
            'start'            => ['nullable', 'date'],
            'end'              => ['nullable', 'date', 'after_or_equal:start'],
            'type_id'          => ['nullable', 'integer', 'exists:org_event_types,id'],

            'masons_only'      => ['sometimes', 'boolean'],
            'open_to'          => ['required', 'in:all,members,officers'],
            'degree_required'  => ['required', 'in:none,entered apprentice,fellowcraft,master mason'],
            'is_public'        => ['sometimes', 'boolean'],

            'repeats'          => ['sometimes', 'boolean'],
            'rrule'            => ['nullable', 'string'],
        ]);
    }

    private function normalizeDates(array &$data): void
    {
        $allDay = (bool)($data['all_day'] ?? false);

        if (!empty($data['start'])) {
            $start = Carbon::parse($data['start'])->utc();
            if ($allDay) $start = $start->startOfDay();
            $data['start'] = $start;
        }

        if (!empty($data['end'])) {
            $end = Carbon::parse($data['end'])->utc();
            if ($allDay) $end = $end->endOfDay();
            $data['end'] = $end;
        }
    }

    private function toDto(OrgEvent $event, bool $withType = false, ?User $actor = null): array
    {
        $toIsoUtc = function ($v) {
            if (!$v) return null;
            $c = $v instanceof \DateTimeInterface ? Carbon::instance($v) : Carbon::parse($v);
            return $c->copy()->utc()->toIso8601String();
        };

        $dto = [
            'id'              => $event->id,
            'title'           => $event->title,
            'description'     => $event->description,
            'location'        => $event->location,
            'timezone'        => $event->timezone,
            'all_day'         => (bool)$event->all_day,
            'start'           => $toIsoUtc($event->start),
            'end'             => $toIsoUtc($event->end),
            'type_id'         => $event->type_id,

            'masons_only'     => (bool)$event->masons_only,
            'open_to'         => $event->open_to ?? 'all',
            'degree_required' => $event->degree_required ?? 'none',
            'is_public'       => (bool)($event->is_public ?? true),

            'repeats'         => (bool)$event->repeats,
            'rrule'           => $event->rrule,
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
