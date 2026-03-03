<?php

namespace App\Http\Controllers;

use App\Models\EventSignup;
use App\Models\EventSignupReminder;
use App\Services\OrgEventRecurrenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EventSignupUnsubscribeController extends Controller
{
    public function show(Request $request, EventSignup $eventSignup): Response
    {
        $eventSignup->load(['subscriber', 'page.event']);
        $event = $eventSignup->page?->event;
        $next = $event ? app(OrgEventRecurrenceService::class)->nextOccurrence($event) : null;

        return Inertia::render('Public/EventSignup/Unsubscribe', [
            'signup' => [
                'uuid' => $eventSignup->uuid,
                'status' => $eventSignup->status,
                'canceled_at' => optional($eventSignup->canceled_at)?->toIso8601String(),
            ],
            'subscriber' => [
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
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request, EventSignup $eventSignup)
    {
        if ($eventSignup->status === 'canceled') {
            return back()->with('success', 'You were already removed from this signup.');
        }

        DB::transaction(function () use ($eventSignup) {
            $eventSignup->status = 'canceled';
            $eventSignup->canceled_at = now();
            $eventSignup->save();

            // If reminders already exist (Step 4), cancel any unsent ones
            EventSignupReminder::where('event_signup_id', $eventSignup->id)
                ->whereNull('sent_at')
                ->whereNull('canceled_at')
                ->update(['canceled_at' => now()]);
        });

        return back()->with('success', 'You have been removed from this signup.');
    }
}
