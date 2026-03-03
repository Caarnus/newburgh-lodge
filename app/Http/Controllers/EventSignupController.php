<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventSignupRequest;
use App\Models\EventSignup;
use App\Models\EventSignupPage;
use App\Models\EventSubscriber;
use App\Services\EventSignupReminderService;
use App\Services\OrgEventRecurrenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EventSignupController extends Controller
{
    public function show(EventSignupPage $eventSignupPage): Response
    {
        $this->abortIfUnavailable($eventSignupPage);

        $event = $eventSignupPage->event;
        $next = $event ? app(OrgEventRecurrenceService::class)->nextOccurrence($event) : null;

        return Inertia::render('Public/EventSignup/Show', [
            'page' => [
                'id' => $eventSignupPage->id,
                'slug' => $eventSignupPage->slug,
                'title' => $eventSignupPage->title_override ?: ($event->title ?? $event->name ?? 'Event'),
                'description' => $eventSignupPage->description,
                'capacity' => $eventSignupPage->capacity,
                'opens_at' => optional($eventSignupPage->opens_at)->toIso8601String(),
                'closes_at' => optional($eventSignupPage->closes_at)->toIso8601String(),
                'cover_image_url' => $eventSignupPage->cover_image_path
                    ? Storage::url($eventSignupPage->cover_image_path)
                    : null,
            ],
            'event' => [
                'starts_at' => $next ? $next['effective_start_utc']->toIso8601String() : optional($event?->start)?->toIso8601String(),
                'ends_at'   => $next ? $next['effective_end_utc']?->toIso8601String() : optional($event?->end)?->toIso8601String(),
                'location'  => $event?->location,
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreEventSignupRequest $request, EventSignupPage $eventSignupPage)
    {
        $this->abortIfUnavailable($eventSignupPage);

        $data = $request->validated();
        $signupObj = null;

        DB::transaction(function () use ($data, $eventSignupPage, &$signupObj) {
            $subscriber = EventSubscriber::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]
            );

            // If they previously existed, gently update blanks (don’t overwrite existing info)
            $dirty = false;

            if (empty($subscriber->name) && !empty($data['name'])) {
                $subscriber->name = $data['name'];
                $dirty = true;
            }
            if (empty($subscriber->phone) && !empty($data['phone'])) {
                $subscriber->phone = $data['phone'];
                $dirty = true;
            }
            if ($dirty) {
                $subscriber->save();
            }

            $signup = EventSignup::where('event_signup_page_id', $eventSignupPage->id)
                ->where('event_subscriber_id', $subscriber->id)
                ->first();

            $payload = [
                'remind_week_before' => (bool)($data['remind_week_before'] ?? true),
                'remind_day_before' => (bool)($data['remind_day_before'] ?? true),
                'remind_hour_before' => (bool)($data['remind_hour_before'] ?? true),
            ];

            if ($signup) {
                // If they canceled previously, reactivate
                if ($signup->status === 'canceled') {
                    $signup->status = 'active';
                    $signup->canceled_at = null;
                }
                $signup->fill($payload)->save();
                $signupObj = $signup;
            } else {
                $signupObj = EventSignup::create([
                    'event_signup_page_id' => $eventSignupPage->id,
                    'event_subscriber_id' => $subscriber->id,
                    ...$payload,
                ]);
            }
        });

        if ($signupObj) {
            app(EventSignupReminderService::class)->syncForSignup($signupObj);
        }

        return back()->with('success', 'You are signed up! Watch your inbox for reminders.');
    }

    private function abortIfUnavailable(EventSignupPage $page): void
    {
        abort_unless($page->is_enabled, 404);

        if ($page->opens_at && now()->lt($page->opens_at)) {
            abort(404);
        }

        if ($page->closes_at && now()->gt($page->closes_at)) {
            abort(404);
        }
    }
}
