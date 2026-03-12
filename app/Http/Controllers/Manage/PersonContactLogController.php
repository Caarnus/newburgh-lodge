<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\Audit;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonContactLogRequest;
use App\Http\Requests\People\UpdatePersonContactLogRequest;
use App\Models\Person;
use App\Models\PersonContactLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class PersonContactLogController extends Controller
{
    public function store(StorePersonContactLogRequest $request, Person $person): RedirectResponse
    {
        $contactLog = PersonContactLog::query()->create([
            'person_id' => $person->id,
            'contacted_at' => $request->filled('contacted_at')
                ? Carbon::parse($request->string('contacted_at')->toString())
                : now(),
            'contact_type' => $request->string('contact_type')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
            'created_by' => $request->user()?->id,
        ]);

        Audit::log(
            $request,
            'person_contact_log.created',
            $person,
            changes: [
                'after' => [
                    'id' => $contactLog->id,
                    'contacted_at' => optional($contactLog->contacted_at)?->toDateTimeString(),
                    'contact_type' => $contactLog->contact_type,
                    'notes' => $contactLog->notes,
                ],
            ],
            meta: [
                'from' => $request->string('from')->toString() ?: null,
            ],
            secondary: $contactLog,
        );

        return back()->with('success', 'Contact logged.');
    }

    public function update(UpdatePersonContactLogRequest $request, Person $person, PersonContactLog $contactLog): RedirectResponse
    {
        abort_unless((int) $contactLog->person_id === (int) $person->id, 404);

        $before = [
            'contacted_at' => optional($contactLog->contacted_at)?->toDateTimeString(),
            'contact_type' => $contactLog->contact_type,
            'notes' => $contactLog->notes,
        ];

        $contactLog->update([
            'contacted_at' => Carbon::parse($request->string('contacted_at')->toString()),
            'contact_type' => $request->string('contact_type')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
        ]);
        $updated = $contactLog->fresh();

        Audit::log(
            $request,
            'person_contact_log.updated',
            $person,
            changes: [
                'before' => $before,
                'after' => [
                    'contacted_at' => optional($updated?->contacted_at)->toDateTimeString(),
                    'contact_type' => $updated?->contact_type,
                    'notes' => $updated?->notes,
                ],
            ],
            meta: [
                'contact_log_id' => $contactLog->id,
                'from' => $request->string('from')->toString() ?: null,
            ],
            secondary: $contactLog,
        );

        return back()->with('success', 'Contact log updated.');
    }
}
