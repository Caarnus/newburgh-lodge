<?php

namespace App\Http\Controllers;

use App\Models\PastMaster;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PastMasterController extends Controller
{
    public function index()
    {
        $pastMasters = PastMaster::orderByDesc('year')->get();

        return Inertia::render('PastMasters', [
            'pastMasters' => $pastMasters
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'year' => ['required'],
            'deceased' => ['boolean'],
        ]);

        return PastMaster::create($data);
    }

    public function show(PastMaster $pastMaster)
    {
        return $pastMaster;
    }

    public function update(Request $request, PastMaster $pastMaster)
    {
        $data = $request->validate([
            'name' => ['required'],
            'year' => ['required'],
            'deceased' => ['boolean'],
        ]);

        $pastMaster->update($data);

        return $pastMaster;
    }

    public function destroy(PastMaster $pastMaster)
    {
        $pastMaster->delete();

        return response()->json();
    }
}
