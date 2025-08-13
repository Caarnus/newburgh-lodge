<?php

use App\Http\Controllers\OrgEventController;
use App\Models\OrgEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/api/events', function() {
    return OrgEvent::where('is_public', true)->get(['id','title','start','end']);
});

Route::get('/api/events', [OrgEventController::class, 'fetchEvents'])->name('api.events.fetch');
