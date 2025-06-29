<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\JeopardyQuestionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact');

Route::get('/history', function () {
    return Inertia::render('History');
})->name('history');

Route::get('/officers', function () {
    return Inertia::render('Officers');
})->name('officers');

Route::get('/past-masters', function () {
    return Inertia::render('PastMasters');
})->name('past-masters');

Route::get('/faq', function () {
    return Inertia::render('Questions');
})->name('faq');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/jeopardy', [JeopardyQuestionController::class,'index'])
        ->name('jeopardy.index');
    Route::get('/jeopardy/board', [JeopardyQuestionController::class,'getBoard'])
        ->name('jeopardy.board');
});
