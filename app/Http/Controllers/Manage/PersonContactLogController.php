<?php

namespace App\Http\Controllers\Manage;

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
        PersonContactLog::query()->create([
            'person_id' => $person->id,
            'contacted_at' => $request->filled('contacted_at')
                ? Carbon::parse($request->string('contacted_at')->toString())
                : now(),
            'contact_type' => $request->string('contact_type')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Contact logged.');
    }

    public function update(UpdatePersonContactLogRequest $request, Person $person, PersonContactLog $contactLog): RedirectResponse
    {
        abort_unless((int) $contactLog->person_id === (int) $person->id, 404);

        $contactLog->update([
            'contacted_at' => Carbon::parse($request->string('contacted_at')->toString()),
            'contact_type' => $request->string('contact_type')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return back()->with('success', 'Contact log updated.');
    }
}
