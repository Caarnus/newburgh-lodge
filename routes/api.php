<?php

use App\Models\LodgeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/api/events', function() {
    return LodgeEvent::where('is_public', true)->get(['id','title','start','end']);
});
