<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\Audit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Officers\UpsertPastMasterRequest;
use App\Models\MemberProfile;
use App\Models\PastMaster;
use Illuminate\Http\RedirectResponse;

class PastMasterManagementController extends Controller
{
    public function store(UpsertPastMasterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['deceased'] = $request->boolean('deceased');

        $pastMaster = PastMaster::create($data);
        $this->markLinkedPersonAsPastMaster($pastMaster->person_id);
        $pastMaster->load('person.memberProfile');

        Audit::log(
            $request,
            'past_masters.created',
            subject: $pastMaster,
            changes: [
                'after' => $this->snapshot($pastMaster),
            ],
        );

        return back()->with('success', 'Past master added.');
    }

    public function update(UpsertPastMasterRequest $request, PastMaster $pastMaster): RedirectResponse
    {
        $before = $this->snapshot($pastMaster->load('person.memberProfile'));

        $data = $request->validated();
        $data['deceased'] = $request->boolean('deceased');

        $pastMaster->update($data);
        $this->markLinkedPersonAsPastMaster($pastMaster->person_id);

        $after = $this->snapshot($pastMaster->fresh()->load('person.memberProfile'));

        if ($before !== $after) {
            Audit::log(
                $request,
                'past_masters.updated',
                subject: $pastMaster,
                changes: [
                    'before' => $before,
                    'after' => $after,
                ],
            );
        }

        return back()->with('success', 'Past master updated.');
    }

    public function destroy(\Illuminate\Http\Request $request, PastMaster $pastMaster): RedirectResponse
    {
        $before = $this->snapshot($pastMaster->load('person.memberProfile'));

        $pastMaster->delete();

        Audit::log(
            $request,
            'past_masters.deleted',
            subject: $pastMaster,
            changes: [
                'before' => $before,
            ],
        );

        return back()->with('success', 'Past master removed.');
    }

    protected function snapshot(PastMaster $pastMaster): array
    {
        return [
            'id' => $pastMaster->id,
            'name' => $pastMaster->name,
            'year' => $pastMaster->year,
            'deceased' => (bool) $pastMaster->deceased,
            'person_id' => $pastMaster->person_id,
            'person_name' => $pastMaster->person?->display_name,
            'member_number' => $pastMaster->person?->memberProfile?->member_number,
            'is_deceased' => (bool) ($pastMaster->deceased || $pastMaster->person?->is_deceased),
        ];
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
