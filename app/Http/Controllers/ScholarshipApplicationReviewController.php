<?php

namespace App\Http\Controllers;

use App\Helpers\AppConstants;
use App\Http\Requests\ScholarshipApplicationReviewRequest;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipApplicationReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ScholarshipApplicationReviewController extends Controller
{
    public function index(Request $request)
    {
        $cycleYear = $request->integer('cycle_year', now()->year);
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));

        $query = ScholarshipApplication::query()
            ->where('cycle_year', $cycleYear)
            ->withCount('reviews')
            ->withAvg('reviews', 'score')
            ->orderByDesc('submitted_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $apps = $query->paginate(25)->withQueryString();

        // Add my score for this page (single query)
        $user = $request->user();
        $myReviews = ScholarshipApplicationReview::query()
            ->where('user_id', $user->id)
            ->whereIn('scholarship_application_id', $apps->getCollection()->pluck('id'))
            ->get()
            ->keyBy('scholarship_application_id');

        $apps->getCollection()->transform(function ($app) use ($myReviews) {
            $app->my_score = optional($myReviews->get($app->id))->score;
            $app->my_notes = optional($myReviews->get($app->id))->notes;

            // Attachments: provide download URLs for table access
            $attachments = $app->attachments ?? [];
            $app->attachments = collect($attachments)->values()->map(function ($a, $idx) use ($app) {
                return [
                    'name' => $a['name'] ?? ('Attachment ' . ($idx + 1)),
                    'url' => route('manage.scholarships.attachments.download', [$app->id, $idx]),
                ];
            })->all();

            return $app;
        });

        $cycleYears = ScholarshipApplication::pluck('cycle_year')->unique()->sort()->values()->all();
        $cycleYearOptions = [];
        foreach ($cycleYears as $cy) {
            $cycleYearOptions[] = ['label' => (string) $cy, 'value' => $cy];
        }

        return Inertia::render('Admin/Scholarship/Index', [
            'applications' => $apps,
            'filters' => [
                'cycle_year' => $cycleYear,
                'status' => $status,
                'search' => $search,
            ],
            'statusOptions' => [
                ['label' => 'All', 'value' => ''],
                AppConstants::SCHOLARSHIP_APPLICATION_STATUS_PENDING,
                AppConstants::SCHOLARSHIP_APPLICATION_STATUS_NEW,
                AppConstants::SCHOLARSHIP_APPLICATION_STATUS_REVIEW,
                AppConstants::SCHOLARSHIP_APPLICATION_STATUS_FINALIST,
                AppConstants::SCHOLARSHIP_APPLICATION_STATUS_AWARDED,
                AppConstants::SCHOLARSHIP_APPLICATION_STATUS_DECLINED,
            ],
            'cycleYearOptions' => $cycleYearOptions,
        ]);
    }

    public function show(Request $request, ScholarshipApplication $scholarshipApplication)
    {
        $scholarshipApplication->load(['reviews.user']);

        // Add attachment download URLs
        $attachments = $scholarshipApplication->attachments ?? [];
        $scholarshipApplication->attachments = collect($attachments)->values()->map(function ($a, $idx) use ($scholarshipApplication) {
            return [
                'name' => $a['name'] ?? ('Attachment ' . ($idx + 1)),
                'url' => route('manage.scholarships.attachments.download', [$scholarshipApplication->id, $idx]),
            ];
        })->all();

        $myReview = $scholarshipApplication->reviews
            ->firstWhere('user_id', $request->user()->id);

        return Inertia::render('Admin/Scholarship/Show', [
            'application' => $scholarshipApplication,
            'myReview' => $myReview ? [
                'score' => (float) $myReview->score,
                'notes' => $myReview->notes,
            ] : null,
        ]);
    }

    public function upsert(ScholarshipApplicationReviewRequest $request, ScholarshipApplication $scholarshipApplication)
    {
        $hadAnyReviews = $scholarshipApplication->reviews()->exists();

        ScholarshipApplicationReview::updateOrCreate(
            [
                'scholarship_application_id' => $scholarshipApplication->id,
                'user_id' => $request->user()->id,
            ],
            [
                'score' => $request->input('score'),
                'notes' => $request->input('notes'),
            ]
        );

        if (!$hadAnyReviews && in_array($scholarshipApplication->status, ['new', 'pending_verification', null, ''], true)) {
            $scholarshipApplication->status = 'in_review';
            $scholarshipApplication->save();
        }

        return redirect()
            ->back()
            ->with('success', 'Your score has been saved.')
            ->setStatusCode(303);
    }

    public function updateApplicationStatus(Request $request, ScholarshipApplication $scholarshipApplication)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', AppConstants::SCHOLARSHIP_APPLICATION_STATUS_LIST)],
        ]);

        $scholarshipApplication->status = $data['status'];
        $scholarshipApplication->save();

        return redirect()
            ->back()
            ->with('success', 'Application status updated.')
            ->setStatusCode(303);
    }

    public function destroyApplication(Request $request, ScholarshipApplication $scholarshipApplication)
    {
        // Purge attachments from disk (recommended because these contain PII)
        $attachments = $scholarshipApplication->attachments ?? [];
        foreach ($attachments as $a) {
            $path = $a['path'] ?? null;
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }

        $scholarshipApplication->attachments = null;
        $scholarshipApplication->save();

        $scholarshipApplication->delete();

        return redirect()
            ->route('manage.scholarships.index', ['cycle_year' => $scholarshipApplication->cycle_year])
            ->with('success', 'Application deleted.')
            ->setStatusCode(303);
    }

    public function destroy(ScholarshipApplicationReview $scholarshipApplicationReview)
    {
        $scholarshipApplicationReview->delete();

        return response()->json();
    }

    public function downloadAttachment(ScholarshipApplication $scholarshipApplication, int $index)
    {
        $attachments = $scholarshipApplication->attachments ?? [];
        abort_unless(isset($attachments[$index]), 404);

        $path = $attachments[$index]['path'] ?? null;
        abort_unless(is_string($path) && $path !== '', 404);

        $name = $attachments[$index]['name'] ?? basename($path);

        return Storage::disk('public')->download($path, $name);
    }
}
