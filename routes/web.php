<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentTileController;
use App\Http\Controllers\EventSignupController;
use App\Http\Controllers\EventSignupManageController;
use App\Http\Controllers\EventSignupUnsubscribeController;
use App\Http\Controllers\GalleryAdminController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\JeopardyQuestionController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrgEventController;
use App\Http\Controllers\PastMasterController;
use App\Http\Controllers\ScholarshipApplicationController;
use App\Http\Controllers\ScholarshipApplicationReviewController;
use App\Http\Controllers\UserAdminController;
use App\Models\Newsletter;
use App\Models\OrgEvent;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ContentTileController::class, 'welcome'])->name('welcome');
Route::get('/dashboard', [ContentTileController::class, 'welcome'])->name('dashboard');

Route::get('/'.config('site.newsletter_route'), [NewsLetterController::class, 'index'])
    ->name('newsletters.index');
Route::get('/'.config('site.newsletter_route').'/{newsletter}', [NewsLetterController::class, 'show'])
    ->where('newsletter', '[0-9]+')
    ->name('newsletters.show');

Route::get('/events', [OrgEventController::class, 'index'])
    ->name('events.index');
Route::get('/events/{event}', [OrgEventController::class, 'show'])
    ->where('event', '[0-9]+')
    ->name('events.show');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->name('contact.submit')
    ->middleware('throttle:10,1');

Route::get('/history', function () {
    return Inertia::render('History');
})->name('history');

Route::get('/officers', function () {
    return Inertia::render('Officers');
})->name('officers');


Route::get('/past-masters', [PastMasterController::class, 'index'])
    ->name('past-masters.index');

Route::get('/faq', function () {
    return Inertia::render('Questions');
})->name('faq');

Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/gallery/{album:slug}', [GalleryController::class, 'show'])->name('gallery.show');

Route::prefix('signup')->name('public.signup.')->group(function () {
    Route::get('{eventSignupPage}', [EventSignupController::class, 'show'])
        ->name('show');
    Route::post('{eventSignupPage}', [EventSignupController::class, 'store'])
        ->middleware('throttle:event-signups')
        ->name('store');

    Route::get('manage/{eventSignup}', [EventSignupManageController::class, 'show'])
        ->middleware('signed')
        ->name('manage.show');
    Route::patch('manage/{eventSignup}', [EventSignupManageController::class, 'update'])
        ->middleware('signed')
        ->name('manage.update');
    // Unsubscribe flow (signed URL required)
    Route::get('unsubscribe/{eventSignup}', [EventSignupUnsubscribeController::class, 'show'])
        ->middleware('signed')
        ->name('unsubscribe.show');
    Route::post('unsubscribe/{eventSignup}', [EventSignupUnsubscribeController::class, 'store'])
        ->middleware('signed')
        ->name('unsubscribe.store');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/jeopardy', [JeopardyQuestionController::class,'index'])
        ->name('jeopardy.index');
    Route::get('/jeopardy/board', [JeopardyQuestionController::class,'getBoard'])
        ->name('jeopardy.board');
    Route::get('/jeopardy/bonus', [JeopardyQuestionController::class,'getBonusQuestions'])
        ->name('jeopardy.bonus');


    Route::get('/admin/users', [UserAdminController::class, 'index'])
        ->name('admin.users.index');
    Route::post('/admin/users', [UserAdminController::class, 'store'])
        ->name('admin.users.store');
    Route::put('/admin/users/bulk', [UserAdminController::class, 'bulkUpdate'])
        ->name('admin.users.bulkUpdate');
    Route::put('/admin/users/{user}', [UserAdminController::class, 'update'])
        ->where('user', '[0-9]+')
        ->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserAdminController::class, 'destroy'])
        ->where('user', '[0-9]+')
        ->name('admin.users.destroy');
    Route::put('/admin/users/{user}/password', [UserAdminController::class, 'setPassword'])
        ->name('admin.users.setPassword');


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
        ->where('newsletter', '[0-9]+')
        ->name('newsletters.update')
        ->can('update', NewsLetter::class);


    Route::get('/events/create', [OrgEventController::class, 'create'])
        ->name('events.create')
        ->can('create', OrgEvent::class);
    Route::post('/events', [OrgEventController::class, 'store'])
        ->name('events.store')
        ->can('create', OrgEvent::class);

    Route::get('/events/{event}/edit', [OrgEventController::class, 'edit'])
        ->where('event', '[0-9]+')
        ->name('events.edit');
    Route::put('/events/{event}', [OrgEventController::class, 'update'])
        ->where('event', '[0-9]+')
        ->name('events.update');

    Route::delete('/events/{event}', [OrgEventController::class, 'destroy'])
        ->where('event', '[0-9]+')
        ->name('events.destroy')
        ->can('delete', OrgEvent::class);

    Route::post('/events/{event}/signup-page', [OrgEventController::class, 'upsertSignupPage'])
        ->name('events.signup-page.upsert');

    Route::delete('/events/{event}/signup-page', [OrgEventController::class, 'destroySignupPage'])
        ->name('events.signup-page.destroy');

    Route::post('/events/{event}/occurrence-overrides', [OrgEventController::class, 'upsertOccurrenceOverride'])
        ->name('events.occurrence-overrides.upsert');

    Route::delete('/events/{event}/occurrence-overrides', [OrgEventController::class, 'destroyOccurrenceOverride'])
        ->name('events.occurrence-overrides.destroy');
});

Route::middleware(['auth:sanctum', 'verified', 'can:manage-gallery'])
    ->prefix('admin/gallery')
    ->group(function () {
        Route::get('/', [GalleryAdminController::class, 'index'])->name('admin.gallery.index');

        Route::post('/albums', [GalleryAdminController::class, 'storeAlbum'])->name('admin.gallery.albums.store');
        Route::put('/albums/{album}', [GalleryAdminController::class, 'updateAlbum'])->name('admin.gallery.albums.update');
        Route::delete('/albums/{album}', [GalleryAdminController::class, 'destroyAlbum'])->name('admin.gallery.albums.destroy');

        Route::post('/photos', [GalleryAdminController::class, 'storePhoto'])->name('admin.gallery.photos.store');
        Route::put('/photos/{photo}', [GalleryAdminController::class, 'updatePhoto'])->name('admin.gallery.photos.update');
        Route::delete('/photos/{photo}', [GalleryAdminController::class, 'destroyPhoto'])->name('admin.gallery.photos.destroy');

        Route::post('/photos/reorder', [GalleryAdminController::class, 'reorderPhotos'])->name('admin.gallery.photos.reorder');
    });


Route::middleware(['auth:sanctum', 'verified', 'can:manage-content'])->prefix('admin/content')->group(function () {
    Route::get('/', [ContentTileController::class, 'index'])->name('admin.content.index');
    Route::post('/tiles', [ContentTileController::class, 'store'])->name('admin.content.store');
    Route::put('/tiles/{tile}', [ContentTileController::class, 'update'])->name('admin.content.update');
    Route::delete('/tiles/{tile}', [ContentTileController::class, 'destroy'])->name('admin.content.destroy');
    Route::post('/reorder', [ContentTileController::class, 'reorder'])->name('admin.content.reorder');
    Route::post('/upload', [ContentTileController::class, 'upload'])->name('admin.content.upload');
});

Route::get('/scholarship', [ScholarshipApplicationController::class, 'intro'])
    ->name('scholarship.intro');
Route::get('/scholarship/apply', [ScholarshipApplicationController::class, 'apply'])
    ->name('scholarship.apply');
Route::post('/scholarship/apply', [ScholarshipApplicationController::class, 'store'])
    ->name('scholarship.apply.store');
Route::get('/scholarship/thanks', [ScholarshipApplicationController::class, 'thanks'])
    ->name('scholarship.thanks');
Route::get('/scholarship/verify/{scholarshipApplication}/{token}', [ScholarshipApplicationController::class, 'verify'])
    ->name('scholarship.verify');

Route::middleware(['auth', 'can:review scholarship applications'])
    ->prefix('manage/scholarship')
    ->group(function () {
        Route::get('/', [ScholarshipApplicationReviewController::class, 'index'])
            ->name('manage.scholarships.index');
        Route::get('/{scholarshipApplication}', [ScholarshipApplicationReviewController::class, 'show'])
            ->name('manage.scholarships.show');
        Route::get('/{scholarshipApplication}/attachments/{index}', [ScholarshipApplicationReviewController::class, 'downloadAttachment'])
            ->whereNumber('index')
            ->name('manage.scholarships.attachments.download');
        Route::post('/{scholarshipApplication}/review', [ScholarshipApplicationReviewController::class, 'upsert'])
            ->name('manage.scholarships.review.upsert');
        Route::patch('/{scholarshipApplication}/status', [ScholarshipApplicationReviewController::class, 'updateApplicationStatus'])
            ->name('manage.scholarships.status.update');
        Route::delete('/{scholarshipApplication}', [ScholarshipApplicationReviewController::class, 'destroyApplication'])
            ->name('manage.scholarships.destroy');
    });
