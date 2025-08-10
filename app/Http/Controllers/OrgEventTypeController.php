<?php

namespace App\Http\Controllers;

use App\Models\OrgEventType;
use Illuminate\Http\Request;

class OrgEventTypeController extends Controller
{
    public function index()
    {
        return OrgEventType::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required'],
        ]);

        return OrgEventType::create($data);
    }

    public function show(OrgEventType $orgEventType)
    {
        return $orgEventType;
    }

    public function update(Request $request, OrgEventType $orgEventType)
    {
        $data = $request->validate([
            'category' => ['required'],
        ]);

        $orgEventType->update($data);

        return $orgEventType;
    }

    public function destroy(OrgEventType $orgEventType)
    {
        $orgEventType->delete();

        return response()->json();
    }
}
