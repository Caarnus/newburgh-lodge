<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use App\Models\PastMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PastMasterController extends Controller
{
    public function index()
    {
        $pastMasters = PastMaster::query()
            ->with('person')
            ->orderByDesc('year')
            ->orderBy('name')
            ->get()
            ->map(fn (PastMaster $pastMaster) => [
                'id' => $pastMaster->id,
                'name' => $pastMaster->name,
                'year' => $pastMaster->year,
                'deceased' => (bool) $pastMaster->deceased,
                'person_id' => $pastMaster->person_id,
                'is_deceased' => (bool) ($pastMaster->deceased || $pastMaster->person?->is_deceased),
            ])
            ->values()
            ->all();

        return Inertia::render('PastMasters', [
            'pastMasters' => $pastMasters
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'string', 'max:32'],
            'deceased' => ['nullable', 'boolean'],
            'person_id' => ['nullable', 'integer', Rule::exists('member_profiles', 'person_id')],
        ]);

        $data['deceased'] = $request->boolean('deceased');

        $pastMaster = PastMaster::create($data);
        $this->markLinkedPersonAsPastMaster($pastMaster->person_id);

        return $pastMaster;
    }

    public function show(PastMaster $pastMaster)
    {
        return $pastMaster;
    }

    public function update(Request $request, PastMaster $pastMaster)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'string', 'max:32'],
            'deceased' => ['nullable', 'boolean'],
            'person_id' => ['nullable', 'integer', Rule::exists('member_profiles', 'person_id')],
        ]);

        $data['deceased'] = $request->boolean('deceased');

        $pastMaster->update($data);
        $this->markLinkedPersonAsPastMaster($pastMaster->person_id);

        return $pastMaster;
    }

    public function destroy(PastMaster $pastMaster)
    {
        $pastMaster->delete();

        return response()->json();
    }

    protected function markLinkedPersonAsPastMaster(?int $personId): void
    {
        if (! $personId) {
            return;
        }

        MemberProfile::query()
            ->where('person_id', $personId)
            ->update(['past_master' => true]);
    }
}
