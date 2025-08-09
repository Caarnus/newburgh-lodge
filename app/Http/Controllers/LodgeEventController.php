<?php

namespace App\Http\Controllers;

use App\Models\LodgeEvent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class LodgeEventController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', LodgeEvent::class);

        return LodgeEvent::all();
    }

    public function store(Request $request)
    {
        $this->authorize('create', LodgeEvent::class);

        $data = $request->validate([
            'title' => ['required'],
            'description' => ['nullable'],
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date'],
            'location' => ['nullable'],
            'is_public' => ['boolean'],
        ]);

        return LodgeEvent::create($data);
    }

    public function show(LodgeEvent $lodgeEvent)
    {
        $this->authorize('view', $lodgeEvent);

        return $lodgeEvent;
    }

    public function update(Request $request, LodgeEvent $lodgeEvent)
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

    public function destroy(LodgeEvent $lodgeEvent)
    {
        $this->authorize('delete', $lodgeEvent);

        $lodgeEvent->delete();

        return response()->json();
    }
}
