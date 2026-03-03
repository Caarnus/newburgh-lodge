<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEventSignupPreferencesRequest;
use App\Models\EventSignup;
use App\Services\EventSignupReminderService;
use App\Services\OrgEventRecurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class EventSignupManageController extends Controller
{
    public function show(Request $request, EventSignup $eventSignup): Response
    {
        $eventSignup->load(['subscriber', 'page.event']);

        // Try to reuse the same expiration window from the current signed URL (if present)
        $expiresAt = $request->query('expires')
            ? CarbonImmutable::createFromTimestamp((int) $request->query('expires'))
            : now()->addDays(30);

        if ($expiresAt->isPast()) {
            $expiresAt = now()->addDays(30);
        }

        $unsubscribeUrl = URL::temporarySignedRoute(
            'public.signup.unsubscribe.show',
            $expiresAt,
            ['eventSignup' => $eventSignup]
        );

        $event = $eventSignup->page?->event;
        $next = $event ? app(OrgEventRecurrenceService::class)->nextOccurrence($event) : null;

        return Inertia::render('Public/EventSignup/Manage', [
            'signup' => [
                'uuid' => $eventSignup->uuid,
                'status' => $eventSignup->status,
                'remind_week_before' => (bool) $eventSignup->remind_week_before,
                'remind_day_before' => (bool) $eventSignup->remind_day_before,
                'remind_hour_before' => (bool) $eventSignup->remind_hour_before,
                'canceled_at' => optional($eventSignup->canceled_at)?->toIso8601String(),
            ],
            'subscriber' => [
                'name' => $eventSignup->subscriber?->name,
                'email' => $eventSignup->subscriber?->email,
            ],
            'page' => [
                'title' => $eventSignup->page?->title_override
                    ?: ($event?->title ?? $event?->name ?? 'Event'),
            ],
            'event' => [
                'starts_at' => $next ? $next['effective_start_utc']->toIso8601String() : optional($event?->start)?->toIso8601String(),
                'ends_at'   => $next ? $next['effective_end_utc']?->toIso8601String() : optional($event?->end)?->toIso8601String(),
                'location'  => $event?->location,
            ],
            'unsubscribe_url' => $unsubscribeUrl,
        ]);
    }

    public function update(UpdateEventSignupPreferencesRequest $request, EventSignup $eventSignup)
    {
        $eventSignup->fill($request->validated())->save();

        app(EventSignupReminderService::class)->syncForSignup($eventSignup->fresh(['page.event']));

        return back()->with('success', 'Preferences updated.');
    }
}
