<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentTileController;
use App\Http\Controllers\EventSignupController;
use App\Http\Controllers\EventSignupManageController;
use App\Http\Controllers\EventSignupUnsubscribeController;
use App\Http\Controllers\GalleryAdminController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\JeopardyQuestionController;
use App\Helpers\People\PeoplePermissions;
use App\Http\Controllers\Manage\MemberDirectoryController;
use App\Http\Controllers\Manage\MemberRosterImportController;
use App\Http\Controllers\Manage\OrphanDirectoryController;
use App\Http\Controllers\Manage\PersonDirectoryController;
use App\Http\Controllers\Manage\RelativeDirectoryController;
use App\Http\Controllers\Manage\UserPersonLinkController;
use App\Http\Controllers\Manage\WidowDirectoryController;
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

$newsletterRoute = trim((string) config('site.newsletter_route'), '/');

/*
|--------------------------------------------------------------------------
| Public Pages
|--------------------------------------------------------------------------
*/

Route::get('/', [ContentTileController::class, 'welcome'])->name('welcome');
Route::get('/dashboard', [ContentTileController::class, 'welcome'])->name('dashboard');

Route::get('/history', fn () => Inertia::render('History'))->name('history');
Route::get('/officers', fn () => Inertia::render('Officers'))->name('officers');
Route::get('/faq', fn () => Inertia::render('Questions'))->name('faq');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->name('contact.submit')
    ->middleware('throttle:10,1');

Route::get('/past-masters', [PastMasterController::class, 'index'])->name('past-masters.index');

/*
|--------------------------------------------------------------------------
| Public Newsletters
|--------------------------------------------------------------------------
*/

Route::prefix($newsletterRoute)
    ->name('newsletters.')
    ->group(function () {
        Route::get('/', [NewsletterController::class, 'index'])->name('index');
        Route::get('/{newsletter}', [NewsletterController::class, 'show'])
            ->whereNumber('newsletter')
            ->name('show');
    });

/*
|--------------------------------------------------------------------------
| Public Events & Gallery
|--------------------------------------------------------------------------
*/

Route::prefix('events')
    ->name('events.')
    ->group(function () {
        Route::get('/', [OrgEventController::class, 'index'])->name('index');
        Route::get('/{event}', [OrgEventController::class, 'show'])
            ->whereNumber('event')
            ->name('show');
    });

Route::prefix('gallery')
    ->name('gallery.')
    ->group(function () {
        Route::get('/', [GalleryController::class, 'index'])->name('index');
        Route::get('/{album:slug}', [GalleryController::class, 'show'])->name('show');
    });

/*
|--------------------------------------------------------------------------
| Public Event Signup Flows
|--------------------------------------------------------------------------
*/

Route::prefix('signup')
    ->name('public.signup.')
    ->group(function () {
        Route::get('{eventSignupPage}', [EventSignupController::class, 'show'])->name('show');
        Route::post('{eventSignupPage}', [EventSignupController::class, 'store'])
            ->middleware('throttle:event-signups')
            ->name('store');

        Route::prefix('manage')
            ->name('manage.')
            ->group(function () {
                Route::get('{eventSignup}', [EventSignupManageController::class, 'show'])
                    ->middleware('signed')
                    ->name('show');
                Route::patch('{eventSignup}', [EventSignupManageController::class, 'update'])
                    ->middleware('signed')
                    ->name('update');
            });

        Route::prefix('unsubscribe')
            ->name('unsubscribe.')
            ->group(function () {
                Route::get('{eventSignup}', [EventSignupUnsubscribeController::class, 'show'])
                    ->middleware('signed')
                    ->name('show');
                Route::post('{eventSignup}', [EventSignupUnsubscribeController::class, 'store'])
                    ->middleware('signed')
                    ->name('store');
            });
    });

/*
|--------------------------------------------------------------------------
| Public Scholarship Pages
|--------------------------------------------------------------------------
*/

Route::prefix('scholarship')
    ->name('scholarship.')
    ->group(function () {
        Route::get('/', [ScholarshipApplicationController::class, 'intro'])->name('intro');
        Route::get('/apply', [ScholarshipApplicationController::class, 'apply'])->name('apply');
        Route::post('/apply', [ScholarshipApplicationController::class, 'store'])->name('apply.store');
        Route::get('/thanks', [ScholarshipApplicationController::class, 'thanks'])->name('thanks');
        Route::get('/verify/{scholarshipApplication}/{token}', [ScholarshipApplicationController::class, 'verify'])
            ->name('verify');
    });

/*
|--------------------------------------------------------------------------
| Authenticated + Verified Core
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () use ($newsletterRoute) {
    Route::prefix('jeopardy')->name('jeopardy.')->group(function () {
        Route::get('/', [JeopardyQuestionController::class, 'index'])->name('index');
        Route::get('/board', [JeopardyQuestionController::class, 'getBoard'])->name('board');
        Route::get('/bonus', [JeopardyQuestionController::class, 'getBonusQuestions'])->name('bonus');
    });

    Route::prefix('admin/users')
        ->name('admin.users.')
        ->group(function () {
            Route::get('/', [UserAdminController::class, 'index'])->name('index');
            Route::post('/', [UserAdminController::class, 'store'])->name('store');
            Route::put('/bulk', [UserAdminController::class, 'bulkUpdate'])->name('bulkUpdate');
            Route::put('/{user}', [UserAdminController::class, 'update'])->whereNumber('user')->name('update');
            Route::delete('/{user}', [UserAdminController::class, 'destroy'])->whereNumber('user')->name('destroy');
            Route::put('/{user}/password', [UserAdminController::class, 'setPassword'])->name('setPassword');
        });

    Route::prefix($newsletterRoute)
        ->name('newsletters.')
        ->group(function () {
            Route::get('/create', [NewsletterController::class, 'create'])
                ->name('create')
                ->can('create', Newsletter::class);
            Route::post('/', [NewsletterController::class, 'store'])
                ->name('store')
                ->can('create', Newsletter::class);
            Route::get('/{newsletter}/edit', [NewsletterController::class, 'edit'])
                ->name('edit')
                ->can('update', Newsletter::class);
            Route::put('/{newsletter}', [NewsletterController::class, 'update'])
                ->whereNumber('newsletter')
                ->name('update')
                ->can('update', Newsletter::class);
        });

    Route::prefix('events')
        ->name('events.')
        ->group(function () {
            Route::get('/create', [OrgEventController::class, 'create'])
                ->name('create')
                ->can('create', OrgEvent::class);
            Route::post('/', [OrgEventController::class, 'store'])
                ->name('store')
                ->can('create', OrgEvent::class);

            Route::get('/{event}/edit', [OrgEventController::class, 'edit'])
                ->whereNumber('event')
                ->name('edit');
            Route::put('/{event}', [OrgEventController::class, 'update'])
                ->whereNumber('event')
                ->name('update');
            Route::delete('/{event}', [OrgEventController::class, 'destroy'])
                ->whereNumber('event')
                ->name('destroy')
                ->can('delete', OrgEvent::class);

            Route::post('/{event}/signup-page', [OrgEventController::class, 'upsertSignupPage'])
                ->name('signup-page.upsert');
            Route::delete('/{event}/signup-page', [OrgEventController::class, 'destroySignupPage'])
                ->name('signup-page.destroy');

            Route::post('/{event}/occurrence-overrides', [OrgEventController::class, 'upsertOccurrenceOverride'])
                ->name('occurrence-overrides.upsert');
            Route::delete('/{event}/occurrence-overrides', [OrgEventController::class, 'destroyOccurrenceOverride'])
                ->name('occurrence-overrides.destroy');
        });
});

/*
|--------------------------------------------------------------------------
| Content/Gallery Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'verified', 'can:manage-gallery'])
    ->prefix('admin/gallery')
    ->name('admin.gallery.')
    ->group(function () {
        Route::get('/', [GalleryAdminController::class, 'index'])->name('index');

        Route::post('/albums', [GalleryAdminController::class, 'storeAlbum'])->name('albums.store');
        Route::put('/albums/{album}', [GalleryAdminController::class, 'updateAlbum'])->name('albums.update');
        Route::delete('/albums/{album}', [GalleryAdminController::class, 'destroyAlbum'])->name('albums.destroy');

        Route::post('/photos', [GalleryAdminController::class, 'storePhoto'])->name('photos.store');
        Route::put('/photos/{photo}', [GalleryAdminController::class, 'updatePhoto'])->name('photos.update');
        Route::delete('/photos/{photo}', [GalleryAdminController::class, 'destroyPhoto'])->name('photos.destroy');

        Route::post('/photos/reorder', [GalleryAdminController::class, 'reorderPhotos'])->name('photos.reorder');
    });

Route::middleware(['auth:sanctum', 'verified', 'can:manage-content'])
    ->prefix('admin/content')
    ->name('admin.content.')
    ->group(function () {
        Route::get('/', [ContentTileController::class, 'index'])->name('index');
        Route::post('/tiles', [ContentTileController::class, 'store'])->name('store');
        Route::put('/tiles/{tile}', [ContentTileController::class, 'update'])->name('update');
        Route::delete('/tiles/{tile}', [ContentTileController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [ContentTileController::class, 'reorder'])->name('reorder');
        Route::post('/upload', [ContentTileController::class, 'upload'])->name('upload');
    });

/*
|--------------------------------------------------------------------------
| Scholarship Review Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'can:review scholarship applications'])
    ->prefix('manage/scholarship')
    ->name('manage.scholarships.')
    ->group(function () {
        Route::get('/', [ScholarshipApplicationReviewController::class, 'index'])->name('index');
        Route::get('/{scholarshipApplication}', [ScholarshipApplicationReviewController::class, 'show'])->name('show');
        Route::get('/{scholarshipApplication}/attachments/{index}', [ScholarshipApplicationReviewController::class, 'downloadAttachment'])
            ->whereNumber('index')
            ->name('attachments.download');
        Route::post('/{scholarshipApplication}/review', [ScholarshipApplicationReviewController::class, 'upsert'])
            ->name('review.upsert');
        Route::patch('/{scholarshipApplication}/status', [ScholarshipApplicationReviewController::class, 'updateApplicationStatus'])
            ->name('status.update');
        Route::delete('/{scholarshipApplication}', [ScholarshipApplicationReviewController::class, 'destroyApplication'])
            ->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| Member Directory Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])
    ->prefix('manage/member-directory')
    ->name('manage.member-directory.')
    ->group(function () {
        Route::get('/', function () {
            $user = request()->user();

            if ($user?->can(PeoplePermissions::VIEW_MEMBER_DIRECTORY)) {
                return redirect()->route('manage.member-directory.members.index');
            }

            if ($user?->can(PeoplePermissions::VIEW_WIDOW_DIRECTORY)) {
                return redirect()->route('manage.member-directory.widows.index');
            }

            if ($user?->can(PeoplePermissions::VIEW_ORPHAN_DIRECTORY)) {
                return redirect()->route('manage.member-directory.orphans.index');
            }

            abort(403);
        })->name('index');

        Route::get('members', [MemberDirectoryController::class, 'index'])
            ->middleware('can:' . PeoplePermissions::VIEW_MEMBER_DIRECTORY)
            ->name('members.index');

        Route::get('members/export', [MemberDirectoryController::class, 'export'])
            ->middleware('can:' . PeoplePermissions::EXPORT_MEMBER_DIRECTORY)
            ->name('members.export');

        Route::get('widows', [WidowDirectoryController::class, 'index'])
            ->middleware('can:' . PeoplePermissions::VIEW_WIDOW_DIRECTORY)
            ->name('widows.index');

        Route::get('orphans', [OrphanDirectoryController::class, 'index'])
            ->middleware('can:' . PeoplePermissions::VIEW_ORPHAN_DIRECTORY)
            ->name('orphans.index');

        Route::get('relatives', [RelativeDirectoryController::class, 'index'])
            ->name('relatives.index');

        Route::get('imports', [MemberRosterImportController::class, 'index'])
            ->middleware('can:' . PeoplePermissions::IMPORT_MEMBER_ROSTER)
            ->name('imports.index');

        Route::post('imports', [MemberRosterImportController::class, 'store'])
            ->middleware('can:' . PeoplePermissions::IMPORT_MEMBER_ROSTER)
            ->name('imports.store');

        Route::get('imports/{importBatch}', [MemberRosterImportController::class, 'show'])
            ->middleware('can:' . PeoplePermissions::IMPORT_MEMBER_ROSTER)
            ->name('imports.show');

        Route::post('imports/{importBatch}/apply', [MemberRosterImportController::class, 'apply'])
            ->middleware('can:' . PeoplePermissions::IMPORT_MEMBER_ROSTER)
            ->name('imports.apply');

        Route::get('people/create', [PersonDirectoryController::class, 'create'])
            ->middleware('can:' . PeoplePermissions::UPDATE_MEMBER_RECORDS)
            ->name('people.create');

        Route::post('people', [PersonDirectoryController::class, 'store'])
            ->middleware('can:' . PeoplePermissions::UPDATE_MEMBER_RECORDS)
            ->name('people.store');

        Route::get('people/search-for-user-link', [UserPersonLinkController::class, 'searchPeople'])
            ->middleware('can:' . PeoplePermissions::UPDATE_MEMBER_RECORDS)
            ->name('people.search-for-user-link');

        Route::get('people/{person}', [PersonDirectoryController::class, 'show'])
            ->middleware('can:' . PeoplePermissions::VIEW_MEMBER_DETAILS)
            ->name('people.show');

        Route::get('users/{user}/person-link', [UserPersonLinkController::class, 'show'])
            ->middleware('can:' . PeoplePermissions::UPDATE_MEMBER_RECORDS)
            ->name('users.person-link.show');

        Route::post('users/{user}/person-link', [UserPersonLinkController::class, 'link'])
            ->middleware('can:' . PeoplePermissions::UPDATE_MEMBER_RECORDS)
            ->name('users.person-link.link');

        Route::delete('users/{user}/person-link', [UserPersonLinkController::class, 'unlink'])
            ->middleware('can:' . PeoplePermissions::UPDATE_MEMBER_RECORDS)
            ->name('users.person-link.unlink');
    });
