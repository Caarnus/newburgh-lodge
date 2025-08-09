<?php

namespace App\Http\Controllers;

use App\Models\OrgEvent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class OrgEventController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', OrgEvent::class);

        return OrgEvent::all();
    }

    public function store(Request $request)
    {
        $this->authorize('create', OrgEvent::class);

        $data = $request->validate([
            'title' => ['required'],
            'description' => ['nullable'],
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date'],
            'location' => ['nullable'],
            'is_public' => ['boolean'],
        ]);

        return OrgEvent::create($data);
    }

    public function show(OrgEvent $lodgeEvent)
    {
        $this->authorize('view', $lodgeEvent);

        return $lodgeEvent;
    }

    public function update(Request $request, OrgEvent $lodgeEvent)
    {
        $this->authorize('update', $lodgeEvent);

        $data = $request->validate([
            'title' => ['required'],
            'description' => ['nullable'],
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date'],
            'location' => ['nullable'],
            'is_public' => ['boolean'],
        ]);

        $lodgeEvent->update($data);

        return $lodgeEvent;
    }

    public function destroy(OrgEvent $lodgeEvent)
    {
        $this->authorize('delete', $lodgeEvent);

        $lodgeEvent->delete();

        return response()->json();
    }
}
