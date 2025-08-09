<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\JeopardyQuestionController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\UserAdminController;
use App\Models\Newsletter;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/'.config('site.newsletter_route').'/create', [NewsLetterController::class, 'create'])
        ->name('newsletters.create')
        ->can('create', NewsLetter::class);
    Route::post('/'.config('site.newsletter_route'), [NewsLetterController::class, 'store'])
        ->name('newsletters.store')
        ->can('create', NewsLetter::class);

    Route::get('/'.config('site.newsletter_route').'/{newsletter}/edit', [NewsLetterController::class, 'edit'])
        ->name('newsletters.edit')
        ->can('update', NewsLetter::class);
    Route::put('/'.config('site.newsletter_route').'/{newsletter}', [NewsLetterController::class, 'update'])
        ->name('newsletters.update')
        ->can('update', NewsLetter::class);
});

Route::get('/'.config('site.newsletter_route'), [NewsLetterController::class, 'index'])
    ->name('newsletters.index');
Route::get('/'.config('site.newsletter_route').'/{newsletter}', [NewsLetterController::class, 'show'])
    ->name('newsletters.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/users', [UserAdminController::class, 'index'])
        ->name('admin.users.index');

    Route::post('/admin/users', [UserAdminController::class, 'store'])
        ->name('admin.users.store');

    Route::put('/admin/users/bulk', [UserAdminController::class, 'bulkUpdate'])
        ->name('admin.users.bulkUpdate');

    Route::put('/admin/users/{user}', [UserAdminController::class, 'update'])
        ->name('admin.users.update');

    Route::put('/admin/users/{user}/password', [UserAdminController::class, 'setPassword'])
        ->name('admin.users.setPassword');
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
    Route::get('/jeopardy/bonus', [JeopardyQuestionController::class,'getBonusQuestion'])
        ->name('jeopardy.bonus');
});
