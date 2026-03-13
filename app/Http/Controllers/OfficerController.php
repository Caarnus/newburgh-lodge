<?php

namespace App\Http\Controllers;

use App\Models\LodgeOfficer;
use Inertia\Inertia;
use Inertia\Response;

class OfficerController extends Controller
{
    public function index(): Response
    {
        $officers = LodgeOfficer::query()
            ->with('person.memberProfile')
            ->orderBy('display_order')
            ->get()
            ->map(fn (LodgeOfficer $officer) => [
                'id' => $officer->id,
                'slot_key' => $officer->slot_key,
                'title' => $officer->title,
                'person' => $officer->person ? [
                    'id' => $officer->person->id,
                    'display_name' => $officer->person->display_name,
                    'member_number' => $officer->person->memberProfile?->member_number,
                ] : null,
            ])
            ->values()
            ->all();

        return Inertia::render('Officers', [
            'officers' => $officers,
        ]);
    }
}

